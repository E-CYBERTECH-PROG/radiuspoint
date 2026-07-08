<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterTerminalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\MikrotikApiService;
use App\Services\WireguardService;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Exceptions\StreamException;

class RouterController extends Controller
{
    /**
     * NOC View: Display all routers for the logged-in ISP.
     */
    public function index(Request $request)
    {
        // The BelongsToTenant trait ensures they only see their own hardware
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")->orWhere('ip_address', 'like', "%{$request->search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();
        $models = config('mikrotik_models');

        return view('routers.index', compact('routers', 'models'));
    }

    /**
     * Fleet-wide live status board. Reads the DB snapshot kept fresh by the
     * router:health-check scheduled command rather than live-querying every router on page
     * load — with a real ~0.5-2s round trip per router over WireGuard, N simultaneous queries
     * on every refresh doesn't scale. Each card has its own "Refresh Now" for an on-demand
     * live read of just that one router.
     */
    public function noc()
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();
        $models = config('mikrotik_models');

        return view('routers.noc', compact('routers', 'models'));
    }

    /**
     * Step 1: Display the "Add Router" UI.
     */
    public function create()
    {
        return view('routers.create');
    }

    /**
     * Step 1 Logic: Auto-generate credentials and safely allocate VPN IP.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'routeros_version' => 'required|in:v6,v7',
            'board_model' => 'required|in:' . implode(',', array_keys(config('mikrotik_models'))),
        ]);

        // Auto-IP allocation: the WireGuard/L2TP VPN subnet (10.0.0.0/24) is shared
        // across ALL tenants on this one physical server, so this must scan every
        // router regardless of tenant (withoutGlobalScope) and pick the first gap
        // in actually-used addresses — not derive from auto-increment IDs, which
        // drift out of sync with real usage the moment any router is deleted.
        $usedOctets = Router::withoutGlobalScope('tenant')
            ->pluck('ip_address')
            ->map(fn ($ip) => (int) substr($ip, strrpos($ip, '.') + 1))
            ->all();

        $nextIpPart = 2;
        while (in_array($nextIpPart, $usedOctets, true)) {
            $nextIpPart++;
        }

        if ($nextIpPart > 254) {
            return back()->with('error', 'VPN Subnet exhausted. Contact SuperAdmin.');
        }

        $ipAddress = "10.0.0." . $nextIpPart;

        $secretKey = Str::random(12);
        $wireguard = (new WireguardService())->generateKeypair();

        $router = Router::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'ip_address' => $ipAddress,
            'api_username' => 'adm_' . Str::random(5),
            'api_password' => Str::random(16),
            'status' => 'pending',
            'public_token' => Str::random(24),
            'routeros_version' => $request->routeros_version,
            'board_model' => $request->board_model,
            'secret_key' => $secretKey,
            'wg_public_key' => $wireguard['public'],
            'wg_private_key' => $wireguard['private'],
        ]);

        // Register this router as a RADIUS client. FreeRADIUS's `read_clients`
        // picks this up on its own on the next request cycle — the cron reconciler
        // (ReconcileNetworking) only needs to touch FreeRADIUS if a reload turns out
        // to be necessary; the WireGuard peer itself always needs that reconciler
        // since applying it to the live interface needs CAP_NET_ADMIN this web
        // process doesn't have.
        DB::table('nas')->insert([
            'nasname' => $ipAddress,
            'shortname' => $router->name,
            'type' => 'other',
            'ports' => 3799,
            'secret' => $secretKey,
            'description' => "RadiusPoint router #{$router->id}",
        ]);

        // Real Winbox/Web access, matching BillNasi's own working approach: unique ports on
        // THIS server's public IP, forwarded via nginx's stream{} module through the WireGuard
        // tunnel to the router's real ports. Deterministic from the router's own id (now known,
        // post-create) — collision-free by construction, no allocation-scanning needed since
        // this is our own server's port namespace, not router-side state.
        $router->update([
            'web_proxy_port' => 20000 + $router->id,
            'winbox_proxy_port' => 21000 + $router->id,
        ]);

        return redirect()->route('routers.provision', $router->id);
    }

    /**
     * Step 2: Show the short bootstrap script. The router pastes only this — a
     * connectivity check plus a fetch+import of the real config from
     * NasProvisioningController::startup() — so no credentials ever sit in
     * copy-pasted text (matches the pattern BillNasi's own wizard uses).
     */
    public function provision(Router $router)
    {
        $startupUrl = route('nas.startup', $router->public_token);
        $fetchMode = str_starts_with($startupUrl, 'https://') ? 'https' : 'http';

        $script = "/ip dns set servers=8.8.8.8,8.8.4.4;\n" .
                  ":global rpDiag \"RadiusPoint: router has no internet - check the WAN port is plugged in, has a valid IP, and the router has a valid default route.\";\n" .
                  "if ([:ping google.com count=3]) do={\n" .
                  // /tool fetch never overwrites an existing dst-path file — it silently saves
                  // "startup.1.rsc" instead and leaves the old "startup.rsc" in place, which
                  // would keep getting re-imported forever on every retry. Remove it first so a
                  // retry always re-fetches and re-imports the current script, not a stale copy.
                  "  :if ([:len [/file find name=startup.rsc]] > 0) do={ /file remove startup.rsc };\n" .
                  "  /tool fetch url=\"{$startupUrl}\" mode={$fetchMode} keep-result=yes dst-path=startup.rsc http-method=get;\n" .
                  "  :import startup.rsc;\n" .
                  "} else={\n" .
                  "  :put \$rpDiag;\n" .
                  "}";

        return view('routers.provision', compact('router', 'script'));
    }
    /**
     * Step 2 Logic: Ajax Handshake Verification with Error Catching.
     */
    public function checkStatus(Router $router)
    {
        try {
            $api = new MikrotikApiService();

            if ($api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                $update = ['status' => 'provisioning', 'last_seen' => now()];

                // If the admin didn't pick a specific model at add-time, identify the real
                // hardware now that we can actually reach it, instead of leaving it generic forever.
                if (! $router->board_model || $router->board_model === 'other') {
                    $resource = $api->query('/system/resource/print');
                    if ($detected = $this->detectBoardModel($resource[0]['board-name'] ?? null)) {
                        $update['board_model'] = $detected;
                    }
                }

                $router->update($update);
                $this->enableRadiusOnDefaultProfiles($api);

                return response()->json(['status' => 'success', 'message' => 'Uplink Established!']);
            }

            return response()->json(['status' => 'error', 'message' => 'Hardware unreachable. Ensure script was pasted.'], 400);

        } catch (Exception $e) {
            Log::error("MikroTik Connection Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Network timeout. Try again.'], 500);
        }
    }

    /**
     * Turns on RADIUS auth for the router's default Hotspot profile, and globally for PPP, via
     * the binary API — moved here from the imported .rsc script (see
     * Router::buildProvisioningScript()) after every text-script variant of this same step
     * reliably failed RouterOS's :import parser. Best-effort: a fresh router may not have
     * Hotspot configured at all yet, so a missing "default" profile is not an error.
     */
    protected function enableRadiusOnDefaultProfiles(MikrotikApiService $api): void
    {
        try {
            $id = $api->findId('/ip/hotspot/profile/print', 'name', 'default');

            if ($id) {
                $api->setById('/ip/hotspot/profile/set', $id, ['use-radius' => 'yes']);
            }
        } catch (Exception $e) {
            Log::warning("Could not enable RADIUS on the default Hotspot profile: " . $e->getMessage());
        }

        // Unlike Hotspot, PPP's RADIUS delegation isn't a per-profile property at all — confirmed
        // against real hardware that /ppp/profile/set rejects use-radius outright ("unknown
        // parameter"). It's a single router-wide toggle instead (see provisionPppoe()).
        try {
            $api->query('/ppp/aaa/set', ['use-radius' => 'yes']);
        } catch (Exception $e) {
            Log::warning("Could not enable RADIUS on /ppp/aaa: " . $e->getMessage());
        }
    }

    /**
     * Detail view: board diagram, live stats, Web/Winbox access, rename/model form.
     */
    public function show(Router $router)
    {
        $models = config('mikrotik_models');
        $captivePortal = $router->captivePortal;

        return view('routers.show', compact('router', 'models', 'captivePortal'));
    }

    /**
     * Saves the tenant's captive-portal customization for this router — template choice,
     * logo/color, notice board text. No re-provisioning needed: the actual portal page is
     * served live from our own server (see CaptivePortalController), so this takes effect
     * immediately for the next customer who connects.
     */
    public function updateCaptivePortal(Request $request, Router $router)
    {
        $request->validate([
            'template' => 'required|in:minimal,business,promo',
            'logo_url' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|max:20',
            'notice_title' => 'nullable|string|max:255',
            'notice_body' => 'nullable|string|max:1000',
        ]);

        \App\Models\CaptivePortal::updateOrCreate(
            ['router_id' => $router->id],
            [
                'tenant_id' => $router->tenant_id,
                'template' => $request->template,
                'logo_url' => $request->logo_url,
                'primary_color' => $request->primary_color ?: '#2563eb',
                'notice_title' => $request->notice_title,
                'notice_body' => $request->notice_body,
            ]
        );

        return redirect()->route('routers.show', $router)->with('success', 'Captive portal updated.');
    }

    /**
     * Operational health-check for an already-configured router — unlike checkStatus()
     * (which is ZTP-flow-specific and marks the router 'provisioning'), this reflects
     * real reachability for a router that's already active, and returns live system
     * info (identity, board name, RouterOS version, uptime, CPU/memory) for the UI,
     * matching how BillNasi's own live status polling works.
     */
    public function testConnection(Router $router)
    {
        try {
            $api = new MikrotikApiService();

            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                $router->update(['status' => 'offline']);

                return response()->json(['status' => 'offline', 'message' => 'Hardware unreachable.'], 400);
            }

            $identity = $api->query('/system/identity/print');
            $resource = $api->query('/system/resource/print');
            $detectedBoardName = $resource[0]['board-name'] ?? null;

            $update = ['status' => 'active', 'last_seen' => now()];

            // Same auto-detect fallback for already-provisioned routers that were added as "Other/Generic".
            if ((! $router->board_model || $router->board_model === 'other') && ($detected = $this->detectBoardModel($detectedBoardName))) {
                $update['board_model'] = $detected;
            }

            $router->update($update);
            $this->enableRadiusOnDefaultProfiles($api);

            return response()->json([
                'status' => 'online',
                'identity' => $identity[0]['name'] ?? null,
                'board_model_detected' => $detectedBoardName,
                'version' => $resource[0]['version'] ?? null,
                'uptime' => $resource[0]['uptime'] ?? null,
                'cpu_load' => $resource[0]['cpu-load'] ?? null,
                'free_memory' => $resource[0]['free-memory'] ?? null,
                'total_memory' => $resource[0]['total-memory'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error("Router test connection failed for {$router->ip_address}: " . $e->getMessage());
            $router->update(['status' => 'offline']);

            return response()->json(['status' => 'offline', 'message' => 'Network timeout.'], 500);
        }
    }

    /**
     * Step 3: Fetch Live Interfaces Safely.
     */
    public function configurePorts(Router $router)
    {
        try {
            $api = new MikrotikApiService();
            
            if ($api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                $interfaces = $api->query('/interface/print'); 
                return view('routers.ports', compact('router', 'interfaces'));
            }

            return redirect()->route('routers.provision', $router->id)->with('error', 'Connection lost. Re-verify uplink.');

        } catch (Exception $e) {
            Log::error("Port Fetch Error: " . $e->getMessage());
            return redirect()->route('routers.index')->with('error', 'Router is offline. Cannot fetch ports.');
        }
    }

    /**
     * FINAL STEP: Actually stand up a Hotspot and/or PPPoE server on each interface the admin
     * assigned one to — previously this only recorded the admin's choice in the database and
     * never touched the router at all (no `/ip hotspot`, no pool, no PPPoE binding), which is
     * why customers had nothing to actually connect to even once the tunnel was reachable.
     */
    public function savePorts(Request $request, Router $router)
    {
        $api = new MikrotikApiService();
        $outcome = [];

        if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
            return redirect()->route('routers.provision', $router->id)->with('error', 'Connection lost. Re-verify uplink before configuring ports.');
        }

        // Unlike Hotspot (where use-radius is a per-profile property), PPP's RADIUS delegation
        // is a single router-wide toggle — confirmed against real hardware: /ppp/profile/add
        // rejects use-radius outright ("unknown parameter"). Setting it once here, before the
        // per-interface loop, covers every PPPoE server this request provisions.
        if (in_array('pppoe', $request->ports ?? [], true) || in_array('both', $request->ports ?? [], true)) {
            $api->query('/ppp/aaa/set', ['use-radius' => 'yes']);
        }

        foreach ($request->ports ?? [] as $interface => $role) {
            if ($role === 'none') {
                $outcome[$interface] = ['role' => 'none'];
                continue;
            }

            $interfaceResult = ['role' => $role];

            if (in_array($role, ['hotspot', 'both'], true)) {
                try {
                    $interfaceResult['hotspot'] = $this->provisionHotspot($api, $interface);
                } catch (Exception $e) {
                    Log::warning("Hotspot provisioning failed for {$router->name}/{$interface}: " . $e->getMessage());
                    $interfaceResult['hotspot_error'] = $e->getMessage();
                }
            }

            if (in_array($role, ['pppoe', 'both'], true)) {
                try {
                    $interfaceResult['pppoe'] = $this->provisionPppoe($api, $interface);
                } catch (Exception $e) {
                    Log::warning("PPPoE provisioning failed for {$router->name}/{$interface}: " . $e->getMessage());
                    $interfaceResult['pppoe_error'] = $e->getMessage();
                }
            }

            $outcome[$interface] = $interfaceResult;
        }

        $anyHotspot = collect($outcome)->contains(fn ($r) => in_array($r['role'] ?? null, ['hotspot', 'both'], true));
        if ($anyHotspot) {
            $this->provisionCaptivePortal($api, $router);
        }

        $router->update([
            'port_configuration' => $outcome,
            'status' => 'active',
        ]);

        return redirect()->route('routers.index')->with('success', 'Hardware Bridge fully operational!');
    }

    /**
     * Wires the router up to actually reach the captive portal: a walled-garden rule letting
     * unauthenticated hotspot clients reach our domain (otherwise they can't load the portal
     * page at all before logging in), and a tiny local login.html rewritten to redirect there,
     * carrying RouterOS's own $(...) template variables — substituted server-side into real
     * values before the browser ever sees them — as query params. Best-effort: a failure here
     * shouldn't fail the whole provisioning step, since the router's own default login page
     * keeps working either way and an admin can retry later.
     */
    protected function provisionCaptivePortal(MikrotikApiService $api, Router $router): void
    {
        try {
            $host = parse_url(config('app.url'), PHP_URL_HOST);

            $existingRule = $api->findId('/ip/hotspot/walled-garden/print', 'dst-host', $host);
            if (! $existingRule) {
                $api->query('/ip/hotspot/walled-garden/add', [
                    'action' => 'allow',
                    'dst-host' => $host,
                    'comment' => 'RadiusPoint captive portal',
                ]);
            }

            $portalUrl = route('captive.show', $router);
            $stub = '<html><head><meta http-equiv="refresh" content="0;url='.$portalUrl
                .'?link-login-only=$(link-login-only)&link-orig=$(link-orig)&mac=$(mac)&ip=$(ip)"></head>'
                .'<body>Redirecting...</body></html>';

            $fileId = $api->findId('/file/print', 'name', 'hotspot/login.html');
            if ($fileId) {
                $api->setById('/file/set', $fileId, ['contents' => $stub]);
            }
        } catch (Exception $e) {
            Log::warning("Captive portal provisioning failed for {$router->name}: " . $e->getMessage());
        }
    }

    /**
     * Real Hotspot server on this interface: its own IP + address pool + a named profile
     * (never "default" — see Router::buildProvisioningScript() for why that's avoided
     * entirely) wired to authenticate via RADIUS, bound together via /ip/hotspot/add.
     */
    protected function provisionHotspot(MikrotikApiService $api, string $interface): array
    {
        $subnet = $api->allocateSubnet();
        $poolName = "rp_hs_{$interface}";
        $profileName = "rp_hs_prof_{$interface}";

        $api->query('/ip/address/add', ['address' => "{$subnet['gateway']}/24", 'interface' => $interface]);
        $api->query('/ip/pool/add', ['name' => $poolName, 'ranges' => $subnet['range']]);
        $api->query('/ip/hotspot/profile/add', [
            'name' => $profileName,
            'hotspot-address' => $subnet['gateway'],
            'use-radius' => 'yes',
        ]);
        // RouterOS defaults a new hotspot server to disabled=yes unless told otherwise —
        // confirmed against real hardware: without this it sits inactive after "successful"
        // provisioning, silently accepting no client traffic at all.
        $api->query('/ip/hotspot/add', [
            'name' => "rp_hs_{$interface}",
            'interface' => $interface,
            'address-pool' => $poolName,
            'profile' => $profileName,
            'disabled' => 'no',
        ]);

        return ['pool' => $poolName, 'profile' => $profileName, 'cidr' => $subnet['cidr']];
    }

    /**
     * Real PPPoE server on this interface: its own client pool + a named profile (again never
     * "default") bound via /interface/pppoe-server/server/add. RADIUS delegation itself is a
     * router-wide toggle (/ppp/aaa, set once in savePorts()), not a per-profile property —
     * confirmed against real hardware that /ppp/profile/add rejects use-radius outright.
     */
    protected function provisionPppoe(MikrotikApiService $api, string $interface): array
    {
        $subnet = $api->allocateSubnet();
        $poolName = "rp_ppp_{$interface}";
        $profileName = "rp_ppp_prof_{$interface}";

        $api->query('/ip/pool/add', ['name' => $poolName, 'ranges' => $subnet['range']]);
        $api->query('/ppp/profile/add', [
            'name' => $profileName,
            'remote-address' => $poolName,
        ]);
        $api->query('/interface/pppoe-server/server/add', [
            'interface' => $interface,
            'service-name' => 'radiuspoint',
            'default-profile' => $profileName,
            'disabled' => 'no',
        ]);

        return ['pool' => $poolName, 'profile' => $profileName, 'cidr' => $subnet['cidr']];
    }

    /**
     * Update Router Details (e.g., Renaming).
     */
    public function update(Request $request, Router $router)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'board_model' => 'required|in:' . implode(',', array_keys(config('mikrotik_models'))),
        ]);

        $router->update([
            'name' => $request->name,
            'board_model' => $request->board_model,
        ]);

        return redirect()->route('routers.show', $router)->with('success', 'Router updated successfully.');
    }

    /**
     * Match RouterOS's own reported board-name (e.g. "RB4011iGS+RM", "hAP ac2") against our
     * catalog's labels, so a router added as "Other/Generic" gets its real photo/specs filled
     * in automatically the first time we can actually reach the hardware and ask it what it is.
     */
    protected function detectBoardModel(?string $boardName): ?string
    {
        if (! $boardName) {
            return null;
        }

        $normalize = fn (string $s) => strtolower(preg_replace('/[^a-z0-9+]/i', '', $s));
        $needle = $normalize($boardName);

        foreach (config('mikrotik_models') as $key => $model) {
            if ($key === 'other') {
                continue;
            }

            $label = $normalize($model['label']);

            if ($label === $needle || str_contains($label, $needle) || str_contains($needle, $label)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Decommission Hardware (Secure Deletion).
     */
    public function destroy(Router $router)
    {
        // In a true SaaS, you might want to check if there are active users on this router first!
        // Remove the matching RADIUS client row now — the WireGuard peer itself is removed by
        // ReconcileNetworking's next run, which diffs live peers against still-existing routers.
        DB::table('nas')->where('nasname', $router->ip_address)->delete();

        $router->delete();

        return redirect()->route('routers.index')->with('success', 'Hardware decommissioned and removed from network.');
    }

    /**
     * Live Monitor page shell — matches BillNasi's per-router monitor dashboard. Each tab's data
     * loads lazily via its own AJAX call below rather than all upfront, since this hardware's
     * real-world links have shown genuine latency/occasional flakiness this session; firing one
     * request per tab on demand is both faster to first paint and more resilient to a single
     * slow call.
     */
    public function monitor(Router $router)
    {
        return view('routers.monitor', compact('router'));
    }

    public function monitorLogs(Router $router)
    {
        // The API has no simple "give me the last N" filter for /log/print, and this router's
        // full buffer ran to 366 entries in testing — capping client-side keeps the payload (and
        // this link's real, sometimes-slow round trip) reasonable without losing anything useful,
        // since RouterOS returns oldest-first and the most recent entries are what actually matter.
        return $this->monitorData($router, fn ($api) => array_slice($api->query('/log/print'), -150));
    }

    public function monitorInterfaces(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/interface/ethernet/print'));
    }

    public function monitorHotspotActive(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/ip/hotspot/active/print'));
    }

    public function monitorPppoeActive(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/ppp/active/print'));
    }

    public function monitorAddresses(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/ip/address/print'));
    }

    public function monitorNeighbors(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/ip/neighbor/print'));
    }

    /**
     * Powers the Traffic tab's interface dropdown. Pulled from /interface/print rather than the
     * Ethernet tab's /interface/ethernet/print, since bandwidth can be watched on any interface
     * (WireGuard, bridge, wlan) — not just physical ports.
     */
    public function monitorInterfaceList(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/interface/print'));
    }

    /**
     * /interface/monitor-traffic normally streams continuous updates over a held-open connection
     * — a poor fit for this app's stateless request/response controllers. RouterOS's own "once"
     * flag (confirmed against real hardware) makes it return exactly one snapshot instead, so the
     * "live" chart is really the frontend polling this endpoint on a timer rather than a true
     * persistent stream — far simpler and fits the same connect-per-request pattern as every
     * other Live Monitor tab.
     */
    public function monitorTraffic(Request $request, Router $router)
    {
        $interface = $request->query('interface');

        if (! $interface) {
            return response()->json(['error' => 'No interface specified.'], 422);
        }

        return $this->monitorData($router, fn ($api) => $api->query('/interface/monitor-traffic', [
            'interface' => $interface,
            'once' => '',
        ]));
    }

    public function monitorDhcpLeases(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/ip/dhcp-server/lease/print'));
    }

    /**
     * RouterOS v7's CAPsMAN-less wireless stack renamed this to /interface/wifi/registration-table
     * on some builds — rather than guess which this router runs, let the normal error path
     * surface RouterOS's own "no such command" cleanly instead of masking it.
     */
    public function monitorWirelessClients(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/interface/wireless/registration-table/print'));
    }

    public function monitorHealth(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/system/health/print'));
    }

    public function monitorQueues(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/queue/simple/print'));
    }

    public function monitorFirewallRules(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/ip/firewall/filter/print'));
    }

    /**
     * /tool/torch has no "once" shortcut like /interface/monitor-traffic, but its own duration=
     * parameter achieves the same bounded-call shape — verified against real hardware to block
     * for ~duration seconds then return final aggregated rows, not stream indefinitely.
     */
    public function monitorTorch(Request $request, Router $router)
    {
        $interface = $request->query('interface');

        if (! $interface) {
            return response()->json(['error' => 'No interface specified.'], 422);
        }

        return $this->monitorData($router, fn ($api) => $api->query('/tool/torch', [
            'interface' => $interface,
            'duration' => '2',
        ]));
    }

    /**
     * Feeds the CPU/Memory History chart. Deliberately separate from testConnection() (which
     * also reads /system/resource/print) — that method writes status/last_seen to the DB on
     * every call, which would spam pointless writes if hit every 2s by this chart's poll loop.
     */
    public function monitorResource(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/system/resource/print'));
    }

    /**
     * RouterOS drops the connection the instant it starts rebooting rather than ever sending
     * !done — the resulting StreamException IS the confirmation the command was received, not
     * a failure. Only reachable inside the action closure after connect() already succeeded, so
     * this can't mask a router that was never reachable to begin with.
     */
    public function reboot(Router $router)
    {
        return $this->monitorAction($router, function ($api) {
            try {
                $api->query('/system/reboot');
            } catch (StreamException $e) {
                // Expected — see method docblock.
            }
        }, 'Reboot command sent.');
    }

    public function toggleInterface(Request $request, Router $router)
    {
        $name = $request->input('interface');
        $disabled = $request->boolean('disabled');

        if (! $name) {
            return response()->json(['error' => 'No interface specified.'], 422);
        }

        return $this->monitorAction($router, function ($api) use ($name, $disabled) {
            $id = $api->findId('/interface/print', 'name', $name);
            if (! $id) {
                throw new Exception("Interface {$name} not found.");
            }
            $api->setById('/interface/set', $id, ['disabled' => $disabled ? 'yes' : 'no']);
        }, $disabled ? 'Interface disabled.' : 'Interface enabled.');
    }

    public function disconnectHotspotUser(Request $request, Router $router)
    {
        $id = $request->input('id');

        if (! $id) {
            return response()->json(['error' => 'No session specified.'], 422);
        }

        return $this->monitorAction($router, fn ($api) => $api->query('/ip/hotspot/active/remove', ['.id' => $id]), 'Session disconnected.');
    }

    public function disconnectPppoeUser(Request $request, Router $router)
    {
        $id = $request->input('id');

        if (! $id) {
            return response()->json(['error' => 'No session specified.'], 422);
        }

        return $this->monitorAction($router, fn ($api) => $api->query('/ppp/active/remove', ['.id' => $id]), 'Session disconnected.');
    }

    /**
     * Blocks via a Hotspot IP binding (type=blocked) rather than a raw firewall rule — the
     * RouterOS-native way to deny a specific client, and consistent with how the rest of this
     * controller manages Hotspot state.
     */
    public function blockHotspotUser(Request $request, Router $router)
    {
        $address = $request->input('address');

        if (! $address) {
            return response()->json(['error' => 'No address specified.'], 422);
        }

        return $this->monitorAction($router, fn ($api) => $api->query('/ip/hotspot/ip-binding/add', [
            'address' => $address,
            'type' => 'blocked',
            'comment' => 'Blocked via RadiusPoint',
        ]), 'Address blocked.');
    }

    /**
     * Paths a typed console command can never target, regardless of who's logged in — these are
     * either irreversible on live hardware (reset/reimage) or manage the router's own auth, which
     * belongs in the provisioning flow, not a free-text box.
     */
    private const TERMINAL_DENIED_PATHS = [
        '/system/reset-configuration',
        '/system/routerboard/upgrade',
        '/system/routerboard/reset',
    ];

    private const TERMINAL_DENIED_PREFIXES = ['/user', '/certificate'];

    public function terminalHistory(Router $router)
    {
        $logs = RouterTerminalLog::with('user')
            ->where('router_id', $router->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'command' => $log->command,
                'result' => $log->result,
                'success' => $log->success,
                'user' => $log->user->name ?? 'Unknown',
                'at' => $log->created_at->diffForHumans(),
            ]);

        return response()->json(['data' => $logs]);
    }

    /**
     * Parses one RouterOS API command line — endpoint path, then space-separated key=value
     * attributes (str_getcsv handles quoted values containing spaces) — and runs it through
     * MikrotikApiService::query() exactly as typed. Because this speaks the binary API protocol
     * rather than RouterOS's CLI/script parser, scripting constructs ([find ...], :if, ;-chains)
     * are structurally inexpressible here; the deny-list above covers the remaining genuinely
     * catastrophic single commands. Every attempt is logged regardless of outcome.
     */
    public function terminalExecute(Request $request, Router $router)
    {
        $line = trim((string) $request->input('command'));

        if (! $line) {
            return response()->json(['error' => 'No command entered.'], 422);
        }

        $parts = str_getcsv($line, ' ');
        $endpoint = array_shift($parts);

        if (! $endpoint || ! str_starts_with($endpoint, '/')) {
            return response()->json(['error' => 'Command must start with a RouterOS API path, e.g. /interface/print'], 422);
        }

        $denied = in_array($endpoint, self::TERMINAL_DENIED_PATHS, true)
            || collect(self::TERMINAL_DENIED_PREFIXES)->contains(fn ($prefix) => str_starts_with($endpoint, $prefix));

        if ($denied) {
            $message = "{$endpoint} is blocked from the console for safety — use Winbox directly if this is genuinely needed.";
            RouterTerminalLog::record(Auth::user(), $router, $line, $message, false);

            return response()->json(['error' => $message], 403);
        }

        $attrs = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $attrs[$key] = $value;
        }

        $user = Auth::user();

        try {
            $api = new MikrotikApiService();

            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                RouterTerminalLog::record($user, $router, $line, 'Hardware unreachable.', false);

                return response()->json(['error' => 'Hardware unreachable.'], 400);
            }

            $result = $this->cleanUtf8($api->query($endpoint, $attrs));
            RouterTerminalLog::record($user, $router, $line, Str::limit(json_encode($result), 5000), true);

            return response()->json(['data' => $result]);
        } catch (Exception $e) {
            RouterTerminalLog::record($user, $router, $line, $e->getMessage(), false);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Shared connect + action + JSON-response plumbing for Live Monitor's remote-control
     * buttons — mirrors monitorData() below but for write actions that return a status message
     * rather than rows of data.
     */
    protected function monitorAction(Router $router, \Closure $action, string $successMessage)
    {
        try {
            $api = new MikrotikApiService();

            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return response()->json(['error' => 'Hardware unreachable.'], 400);
            }

            $action($api);

            return response()->json(['status' => 'ok', 'message' => $successMessage]);
        } catch (Exception $e) {
            Log::warning("Router action failed for {$router->name}: " . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Shared connect + query + JSON-response plumbing for every Live Monitor tab, so each
     * endpoint above is just "which RouterOS path" — connection handling and error shape only
     * live in one place.
     */
    protected function monitorData(Router $router, \Closure $query)
    {
        try {
            $api = new MikrotikApiService();

            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return response()->json(['error' => 'Hardware unreachable.'], 400);
            }

            return response()->json(['data' => $this->cleanUtf8($query($api))]);
        } catch (Exception $e) {
            Log::warning("Monitor data fetch failed for {$router->name}: " . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * RouterOS log messages in particular can carry raw non-UTF-8 bytes (e.g. from device
     * hostnames), which makes json_encode() fail outright rather than just mangling the
     * character — confirmed against real hardware on the /log/print tab specifically. Scrub
     * every string value before it ever reaches response()->json().
     */
    protected function cleanUtf8(array $rows): array
    {
        array_walk_recursive($rows, function (&$value) {
            if (is_string($value)) {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        });

        return $rows;
    }
}
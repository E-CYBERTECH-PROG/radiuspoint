<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterTerminalLog;
use App\Notifications\RouterDecommissionCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
        $search = $this->searchTerm($request);

        $routers = Router::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%");
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

        // Real Winbox/Web access: unique ports on THIS server's public IP, forwarded via
        // nginx's stream{} module through the WireGuard tunnel to the router's real ports.
        // Deterministic from the router's own id (now known,
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
     * NasProvisioningController::startup() — so no credentials ever sit in copy-pasted text.
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
                  // check-certificate=no: a factory-fresh/just-reset router has no correct
                  // system clock yet (no confirmed NTP sync, no RTC battery on some boards), so
                  // RouterOS can reject our Let's Encrypt cert as "not yet valid"/"expired" purely
                  // because its own clock is wrong — this is the classic MikroTik ZTP
                  // chicken-and-egg problem, and shows up in the router's own terminal as an SSL
                  // failure on this very first fetch. The tunnel this script sets up moments later
                  // (WireGuard keypair + RADIUS shared secret) is what actually authenticates the
                  // router going forward, so skipping strict cert validation on just this one
                  // bootstrap download doesn't weaken that.
                  "  /tool fetch url=\"{$startupUrl}\" mode={$fetchMode} check-certificate=no keep-result=yes dst-path=startup.rsc http-method=get;\n" .
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

            // Longer timeout than the 3s default — a freshly-booted router's API service can
            // still be settling right after the provisioning script ran, unlike every other call
            // site here which only ever targets an already-running, previously-proven router.
            if ($api->connect($router->ip_address, $router->api_username, $router->api_password, timeout: 8)) {
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
    public function enableRadiusOnDefaultProfiles(MikrotikApiService $api): void
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
        // interim-update matches the hotspot profile's radius-interim-update — without it PPPoE
        // sessions have the same "radacct usage frozen until disconnect" problem hotspot did.
        // This call runs on every testConnection() (health-check ping), so it doubles as the
        // retroactive fix for PPPoE routers provisioned before this was added — no separate
        // one-off repair needed since PPP's RADIUS delegation is router-wide, not per-profile.
        try {
            $api->query('/ppp/aaa/set', ['use-radius' => 'yes', 'interim-update' => '00:05:00']);
        } catch (Exception $e) {
            Log::warning("Could not enable RADIUS on /ppp/aaa: " . $e->getMessage());
        }

        // Defense-in-depth against expired customers keeping internet access: RouterOS doesn't
        // re-check RADIUS mid-session, so deleting the credential alone doesn't touch a session
        // that's already connected — a customer who was online at the moment they expired would
        // otherwise keep full access until they happened to disconnect on their own.
        // ExpireOverdueUsers adds their session's IP to radiuspoint-expired whenever this rule
        // exists, so this firewall drop is what actually enforces the cutoff.
        try {
            $oldExpiredRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'rp-expired-block');
            if ($oldExpiredRuleId) {
                $api->setById('/ip/firewall/filter/set', $oldExpiredRuleId, [
                    'src-address-list' => 'radiuspoint-expired',
                    'comment' => 'radiuspoint-expired-block',
                ]);
            }

            $dropRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'radiuspoint-expired-block');
            if (! $dropRuleId) {
                $api->query('/ip/firewall/filter/add', [
                    'chain' => 'forward',
                    'action' => 'drop',
                    'src-address-list' => 'radiuspoint-expired',
                    'comment' => 'radiuspoint-expired-block',
                    'place-before' => '0',
                ]);
            }
        } catch (Exception $e) {
            Log::warning("Could not add the radiuspoint-expired firewall rule: " . $e->getMessage());
        }

        // RouterOS's default "defconf: fasttrack" rule marks established/related forward
        // connections to skip all further firewall AND queue processing for the rest of that
        // connection's life — which means a connection that got fasttracked before a customer
        // expired can keep bypassing radiuspoint-expired-block above, and any already-fasttracked
        // connection bypasses FUP's simple-queue throttling too (see EnforceFairUsage). Disabling
        // it (not removing it, so it can be re-enabled manually if a tenant ever needs the
        // throughput) makes both guarantees hold for a connection's full lifetime, not just its
        // first few packets.
        try {
            $fasttrackRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'defconf: fasttrack');
            if ($fasttrackRuleId) {
                $api->setById('/ip/firewall/filter/set', $fasttrackRuleId, ['disabled' => 'yes']);
            }
        } catch (Exception $e) {
            Log::warning("Could not disable the default fasttrack rule: " . $e->getMessage());
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
            'template' => 'required|in:default,business,promo,premium',
            'logo_url' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|max:20',
            'notice_title' => 'nullable|string|max:255',
            'notice_body' => 'nullable|string|max:1000',
            'testimonial_1_text' => 'nullable|string|max:255',
            'testimonial_1_author' => 'nullable|string|max:100',
            'testimonial_2_text' => 'nullable|string|max:255',
            'testimonial_2_author' => 'nullable|string|max:100',
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
                'testimonial_1_text' => $request->testimonial_1_text,
                'testimonial_1_author' => $request->testimonial_1_author,
                'testimonial_2_text' => $request->testimonial_2_text,
                'testimonial_2_author' => $request->testimonial_2_author,
            ]
        );

        return redirect()->route('routers.show', $router)->with('success', 'Captive portal updated.');
    }

    /**
     * Operational health-check for an already-configured router — unlike checkStatus()
     * (which is ZTP-flow-specific and marks the router 'provisioning'), this reflects
     * real reachability for a router that's already active, and returns live system
     * info (identity, board name, RouterOS version, uptime, CPU/memory) for the UI.
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

        try {
            $this->cleanupInvalidHotspotServers($api);
        } catch (Exception $e) {
            Log::warning("Invalid hotspot server cleanup failed for {$router->name}: " . $e->getMessage());
        }

        // Two independent checkboxes per interface (Hotspot / PPPoE) rather than a single
        // none/hotspot/pppoe/both dropdown — derive the same role string the rest of this method
        // already expects, so provisioning logic below is unchanged. all_interfaces[] carries
        // every interface shown on the form (even ones with neither box checked) so an interface
        // being explicitly turned back to "none" is still recorded, matching the old dropdown's
        // default option rather than just being silently omitted.
        $hotspotPorts = $request->input('hotspot_ports', []);
        $pppoePorts = $request->input('pppoe_ports', []);
        $ports = [];
        foreach ($request->input('all_interfaces', []) as $interface) {
            $isHotspot = in_array($interface, $hotspotPorts, true);
            $isPppoe = in_array($interface, $pppoePorts, true);
            $ports[$interface] = match (true) {
                $isHotspot && $isPppoe => 'both',
                $isHotspot => 'hotspot',
                $isPppoe => 'pppoe',
                default => 'none',
            };
        }

        // Unlike Hotspot (where use-radius is a per-profile property), PPP's RADIUS delegation
        // is a single router-wide toggle — /ppp/profile/add rejects use-radius outright
        // ("unknown parameter"). Setting it once here, before the per-interface loop, covers
        // every PPPoE server this request provisions.
        if (in_array('pppoe', $ports, true) || in_array('both', $ports, true)) {
            $api->query('/ppp/aaa/set', ['use-radius' => 'yes']);
        }

        foreach ($ports as $interface => $role) {
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
            try {
                $this->provisionCaptivePortal($api, $router);
                $this->provisionFreeMode($api, $router);
            } catch (Exception $e) {
                Log::warning("Captive portal provisioning failed for {$router->name}: " . $e->getMessage());
            }
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
     * values before the browser ever sees them — as query params. Best-effort during initial
     * provisioning: a failure here shouldn't fail the whole provisioning step. Idempotent, so
     * it also doubles as the handler behind the "Push Portal Files" button on an
     * already-provisioned router (see pushCaptivePortalFiles()) — safe to call repeatedly.
     */
    protected function provisionCaptivePortal(MikrotikApiService $api, Router $router): array
    {
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

        // Retroactive fix for routers provisioned before login-by/radius-interim-update were
        // added to provisionHotspot() — without http-pap, RouterOS silently rejects every plain
        // username+password POST our captive portal's auto-connect submits; without
        // radius-interim-update, radacct usage stays frozen at 0 for the whole session (see
        // provisionHotspot()'s comment), which is why data-cap/FUP enforcement wasn't really
        // working. Scoped to profiles we created — recognizes both the current
        // "radiuspoint_hs_prof_" prefix and the older "rp_hs_prof_" one so routers provisioned
        // before the naming convention changed get migrated too (not orphaned), never "everything
        // except default" — an admin's own manually-named profile isn't ours to rewrite or rename.
        $loginByFixed = 0;
        $interimUpdateFixed = 0;
        $renamed = 0;
        $profiles = $api->query('/ip/hotspot/profile/print');
        foreach ($profiles as $profile) {
            $name = $profile['name'] ?? '';
            $isOurs = str_starts_with($name, 'radiuspoint_hs_prof_') || str_starts_with($name, 'rp_hs_prof_');
            if (! $isOurs) {
                continue;
            }
            if (str_starts_with($name, 'rp_hs_prof_')) {
                $name = 'radiuspoint_hs_prof_'.substr($name, strlen('rp_hs_prof_'));
                $api->setById('/ip/hotspot/profile/set', $profile['.id'], ['name' => $name]);
                $renamed++;
            }
            $current = $profile['login-by'] ?? '';
            if (! str_contains($current, 'http-pap')) {
                $api->setById('/ip/hotspot/profile/set', $profile['.id'], [
                    'login-by' => $current ? $current.',http-pap' : 'http-pap',
                ]);
                $loginByFixed++;
            }
            // RouterOS always reads this back normalized as "5m", never the "00:05:00" form
            // used to set it — comparing against "00:05:00" here would make this "retroactive
            // fix" silently rewrite the same value on every single push.
            if (($profile['radius-interim-update'] ?? '') !== '5m') {
                $api->setById('/ip/hotspot/profile/set', $profile['.id'], [
                    'radius-interim-update' => '00:05:00',
                ]);
                $interimUpdateFixed++;
            }
        }

        // Retroactive fix for hotspot servers provisioned before idle-timeout was set explicitly
        // — see provisionHotspot()'s comment. Servers use the "radiuspoint_hs_server_"/"rp_hs_"
        // prefix (not "..._prof_", that's the profile above) — same recognize-both-migrate-old
        // approach as the profile loop.
        $idleTimeoutFixed = 0;
        $servers = $api->query('/ip/hotspot/print');
        foreach ($servers as $server) {
            $name = $server['name'] ?? '';
            $isOurs = str_starts_with($name, 'radiuspoint_hs_server_') || str_starts_with($name, 'rp_hs_');
            if (! $isOurs) {
                continue;
            }
            if (str_starts_with($name, 'rp_hs_') && ! str_starts_with($name, 'radiuspoint_hs_server_')) {
                $name = 'radiuspoint_hs_server_'.substr($name, strlen('rp_hs_'));
                $api->setById('/ip/hotspot/set', $server['.id'], ['name' => $name]);
                $renamed++;
            }
            if (($server['idle-timeout'] ?? '') !== '2m') {
                $api->setById('/ip/hotspot/set', $server['.id'], ['idle-timeout' => '2m']);
                $idleTimeoutFixed++;
            }
        }

        // Retroactive rename for the hotspot address-pool ("rp_hs_{interface}" ->
        // "radiuspoint_hs_pool_{interface}") — pools aren't referenced by name from anywhere we
        // query here, but renaming them keeps `/ip pool print` consistent with everything else
        // rather than leaving a mismatched leftover name behind.
        $pools = $api->query('/ip/pool/print');
        foreach ($pools as $pool) {
            $name = $pool['name'] ?? '';
            if (str_starts_with($name, 'rp_hs_') && ! str_starts_with($name, 'rp_hs_prof_')) {
                $newName = 'radiuspoint_hs_pool_'.substr($name, strlen('rp_hs_'));
                $api->setById('/ip/pool/set', $pool['.id'], ['name' => $newName]);
                $renamed++;
            } elseif (str_starts_with($name, 'rp_ppp_') && ! str_starts_with($name, 'rp_ppp_prof_')) {
                $newName = 'radiuspoint_ppp_pool_'.substr($name, strlen('rp_ppp_'));
                $api->setById('/ip/pool/set', $pool['.id'], ['name' => $newName]);
                $renamed++;
            }
        }

        // Same idea for PPPoE profiles ("rp_ppp_prof_" -> "radiuspoint_ppp_prof_") — no other
        // fixes apply to these today, just the rename.
        $pppProfiles = $api->query('/ppp/profile/print');
        foreach ($pppProfiles as $profile) {
            $name = $profile['name'] ?? '';
            if (str_starts_with($name, 'rp_ppp_prof_')) {
                $newName = 'radiuspoint_ppp_prof_'.substr($name, strlen('rp_ppp_prof_'));
                $api->setById('/ppp/profile/set', $profile['.id'], ['name' => $newName]);
                $renamed++;
            }
        }

        // Retroactive fix for routers provisioned before buildProvisioningScript() was
        // corrected — it used to point /radius at the server's *public* IP (only correct for
        // dialing the WireGuard tunnel itself), not the tunnel-internal address FreeRADIUS
        // actually binds to. RouterOS reports the resulting broken entry as
        // "connect:Network unreachable" and every login fails with "RADIUS server is not
        // responding" — confirmed against the real test router (id=11), see
        // project_radius_firewall_and_timezone_fixes memory.
        // Also removes stale duplicate entries left by an interrupted/repeated provisioning
        // run (`/radius add` has no "update if exists" form) — a leftover entry with the
        // router's *previous* secret gets tried by RouterOS, fails the server's shared-secret
        // check, and FreeRADIUS silently drops it rather than replying — indistinguishable
        // from "not responding" in the hotspot log alone. Confirmed as the live cause on a
        // real re-provisioned router (id=17). Exactly one entry, with the router's current
        // secret_key and the correct tunnel-internal address, should exist afterward.
        // Scoped to service=hotspot,ppp (matching Router::buildProvisioningScript()'s own
        // `/radius remove [find service=hotspot,ppp]`) so this never touches a /radius client an
        // admin configured for something else entirely (IPsec/L2TP/wireless auth) — those have no
        // reason to share this router's secret_key and would otherwise look "wrong" and get
        // deleted purely for not matching it.
        $correctRadiusIp = config('vpn.server_vpn_ip');
        $radiusFixed = 0;
        $radiusEntries = $api->query('/radius/print');
        foreach ($radiusEntries as $entry) {
            $service = $entry['service'] ?? '';
            if (! str_contains($service, 'hotspot') && ! str_contains($service, 'ppp')) {
                continue;
            }
            if (($entry['secret'] ?? '') !== $router->secret_key) {
                $api->query('/radius/remove', ['.id' => $entry['.id']]);
                $radiusFixed++;
                continue;
            }
            if (($entry['address'] ?? '') !== $correctRadiusIp) {
                $api->setById('/radius/set', $entry['.id'], ['address' => $correctRadiusIp]);
                $radiusFixed++;
            }
        }

        return [
            'walled_garden' => $existingRule ? 'already present' : 'added',
            'login_html' => $fileId ? 'updated' : 'not found — hotspot skin missing hotspot/login.html',
            'login_by_fixed' => $loginByFixed,
            'interim_update_fixed' => $interimUpdateFixed,
            'idle_timeout_fixed' => $idleTimeoutFixed,
            'radius_address_fixed' => $radiusFixed,
            'profiles_renamed' => $renamed,
        ];
    }

    /**
     * Free Mode: a RADIUS-authenticated session gets throttled to a low rate cap (confirmed
     * mechanism — same Mikrotik-Rate-Limit reply attribute FUP enforcement already uses live) and
     * tagged into an address-list via Mikrotik-Group. Confirmed FreeRADIUS's own
     * authorize_reply_query is fully generic (SELECT * FROM radreply WHERE username = ...,
     * ORDER BY id, no attribute-specific logic anywhere) — Mikrotik-Group gets encoded and sent
     * through the exact same code path as Mikrotik-Rate-Limit, which is already proven live (a
     * real customer's session queue enforced the configured cap — see EnforceFairUsage). The
     * router-side profile (radiuspoint_free, address-list=radiuspoint-freemode) is also confirmed
     * present with the right config. The one thing that still can't be verified without a human
     * physically connecting a device is whether that specific customer's traffic then actually
     * gets filtered by the walled-garden firewall rules — everything upstream of that final tap
     * is now confirmed correct, so treat the domain restriction as a secondary layer on top of
     * the rate cap (itself fully proven), not an unverified guess.
     * Idempotent — safe to call on every push, same as provisionCaptivePortal().
     */
    protected function provisionFreeMode(MikrotikApiService $api, Router $router): array
    {
        // Migrate an old "rp_free" profile in place rather than leaving it orphaned alongside a
        // new one — renaming also updates its address-list field so dynamically-tagged sessions
        // start landing in the new-named list going forward.
        $oldProfileId = $api->findId('/ip/hotspot/user/profile/print', 'name', 'rp_free');
        if ($oldProfileId) {
            $api->setById('/ip/hotspot/user/profile/set', $oldProfileId, [
                'name' => 'radiuspoint_free',
                'address-list' => 'radiuspoint-freemode',
            ]);
        }

        $profileId = $api->findId('/ip/hotspot/user/profile/print', 'name', 'radiuspoint_free');
        if (! $profileId) {
            $api->query('/ip/hotspot/user/profile/add', [
                'name' => 'radiuspoint_free',
                'address-list' => 'radiuspoint-freemode',
                'rate-limit' => '96k/96k',
            ]);
        }

        $allowedDomains = [
            '.*whatsapp\\.com',
            '.*whatsapp\\.net',
            '.*facebook\\.com',
            '.*fbcdn\\.net',
            '.*messenger\\.com',
        ];

        $dnsAdded = 0;
        foreach ($allowedDomains as $regexp) {
            $existing = $api->findId('/ip/dns/static/print', 'regexp', $regexp);
            if ($existing) {
                // Matched by regexp regardless of naming generation — make sure its
                // address-list still points at the current name rather than a stale one.
                $api->setById('/ip/dns/static/set', $existing, ['address-list' => 'radiuspoint-freemode-allowed']);
            } else {
                $api->query('/ip/dns/static/add', [
                    'regexp' => $regexp,
                    'type' => 'FWD',
                    'forward-to' => '8.8.8.8',
                    'address-list' => 'radiuspoint-freemode-allowed',
                    'ttl' => '1m',
                    'comment' => 'RadiusPoint free mode',
                ]);
                $dnsAdded++;
            }
        }

        // Same migrate-in-place approach for the two firewall rules — found by their old comment,
        // updated to the new comment + address-list names rather than left stale.
        $oldAcceptRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'rp-freemode-allow');
        if ($oldAcceptRuleId) {
            $api->setById('/ip/firewall/filter/set', $oldAcceptRuleId, [
                'src-address-list' => 'radiuspoint-freemode',
                'dst-address-list' => 'radiuspoint-freemode-allowed',
                'comment' => 'radiuspoint-freemode-allow',
            ]);
        }

        $acceptRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'radiuspoint-freemode-allow');
        if (! $acceptRuleId) {
            $api->query('/ip/firewall/filter/add', [
                'chain' => 'forward',
                'action' => 'accept',
                'src-address-list' => 'radiuspoint-freemode',
                'dst-address-list' => 'radiuspoint-freemode-allowed',
                'comment' => 'radiuspoint-freemode-allow',
            ]);
        }

        $oldDropRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'rp-freemode-block-rest');
        if ($oldDropRuleId) {
            $api->setById('/ip/firewall/filter/set', $oldDropRuleId, [
                'src-address-list' => 'radiuspoint-freemode',
                'comment' => 'radiuspoint-freemode-block-rest',
            ]);
        }

        $dropRuleId = $api->findId('/ip/firewall/filter/print', 'comment', 'radiuspoint-freemode-block-rest');
        if (! $dropRuleId) {
            $api->query('/ip/firewall/filter/add', [
                'chain' => 'forward',
                'action' => 'drop',
                'src-address-list' => 'radiuspoint-freemode',
                'comment' => 'radiuspoint-freemode-block-rest',
            ]);
        }

        return [
            'profile' => $profileId ? 'already present' : 'added',
            'dns_rules_added' => $dnsAdded,
            'firewall_rules' => ($acceptRuleId && $dropRuleId) ? 'already present' : 'added',
        ];
    }

    /**
     * Manual re-run of the captive-portal + free-mode wiring for a router that's already
     * provisioned. `provisionCaptivePortal()` only ran automatically the moment a hotspot port
     * was first saved — any router provisioned before that step existed (or whose walled-garden
     * rule was lost to a factory reset) never got it, and there was previously no way to retry
     * short of re-running the whole port-provisioning flow. This exposes that one step directly.
     */
    public function pushCaptivePortalFiles(Router $router)
    {
        try {
            $api = new MikrotikApiService();

            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return response()->json(['message' => 'Hardware unreachable.'], 400);
            }

            $result = $this->provisionCaptivePortal($api, $router);
            $freeMode = $this->provisionFreeMode($api, $router);

            return response()->json([
                'message' => 'Captive portal files pushed to router.',
                'walled_garden' => $result['walled_garden'],
                'login_html' => $result['login_html'],
                'login_by_fixed' => $result['login_by_fixed'],
                'interim_update_fixed' => $result['interim_update_fixed'],
                'idle_timeout_fixed' => $result['idle_timeout_fixed'],
                'radius_address_fixed' => $result['radius_address_fixed'],
                'profiles_renamed' => $result['profiles_renamed'],
                'free_mode_profile' => $freeMode['profile'],
                'free_mode_firewall' => $freeMode['firewall_rules'],
            ]);
        } catch (Exception $e) {
            Log::error("Captive portal push failed for {$router->name}: " . $e->getMessage());

            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Real Hotspot server on this interface: its own IP + address pool + a named profile
     * (never "default" — see Router::buildProvisioningScript() for why that's avoided
     * entirely) wired to authenticate via RADIUS, bound together via /ip/hotspot/add.
     */
    /**
     * RouterOS marks a /ip hotspot server invalid=yes when it can never actually run — the
     * common cause is its interface being a bridge port, which provisionHotspot() didn't used
     * to account for. Removes the dead server plus its now-orphaned profile/pool so a router
     * provisioned before that fix gets cleaned up the next time ports are saved, rather than
     * accumulating dead config forever. Safe to call repeatedly.
     */
    protected function cleanupInvalidHotspotServers(MikrotikApiService $api): void
    {
        foreach ($api->query('/ip/hotspot/print') as $server) {
            if (($server['invalid'] ?? 'false') !== 'true') {
                continue;
            }

            $api->query('/ip/hotspot/remove', ['.id' => $server['.id']]);

            if ($profileId = $api->findId('/ip/hotspot/profile/print', 'name', $server['profile'] ?? '')) {
                $api->query('/ip/hotspot/profile/remove', ['.id' => $profileId]);
            }

            if ($poolId = $api->findId('/ip/pool/print', 'name', $server['address-pool'] ?? '')) {
                $api->query('/ip/pool/remove', ['.id' => $poolId]);
            }
        }
    }

    protected function provisionHotspot(MikrotikApiService $api, string $interface): array
    {
        // A hotspot server can't actually run on an interface that's itself a bridge port —
        // RouterOS marks it invalid=yes and it never intercepts anything. RouterOS's own
        // factory-default config already bridges every LAN/WiFi port together, so an admin
        // ticking several of those ports as "Hotspot" used to create several dead server
        // entries instead of one working one. Confirmed live: 3 of 4 servers provisioned this
        // way sat invalid while only the one bound to the bridge itself ran. Resolve to the
        // real bridge up front so every bridged port converges on the same server below.
        $bridgePort = $api->queryWhere('/interface/bridge/port/print', 'interface', $interface);
        if ($bridgePort) {
            $interface = $bridgePort[0]['bridge'];
        }

        $serverName = "radiuspoint_hs_server_{$interface}";
        $poolName = "radiuspoint_hs_pool_{$interface}";
        $profileName = "radiuspoint_hs_prof_{$interface}";

        // Idempotent: two bridged ports both resolving to "bridge" above must not try to
        // create the same named pool/profile/server twice (RouterOS rejects duplicate names),
        // and re-running this after the server already exists (e.g. the "Push Portal Files"
        // button) must not touch it again either.
        if (! $api->findId('/ip/hotspot/print', 'name', $serverName)) {
            $subnet = $api->allocateSubnet();

            $api->query('/ip/address/add', ['address' => "{$subnet['gateway']}/24", 'interface' => $interface]);
            $api->query('/ip/pool/add', ['name' => $poolName, 'ranges' => $subnet['range']]);
            // login-by must include http-pap: RouterOS's default of "cookie,http-chap" only
            // accepts a CHAP challenge/response or an existing cookie, silently rejecting the
            // plain username+password POST our captive portal's auto-connect submits. Confirmed
            // against real hardware — every profile provisioned before this fix only had
            // cookie,http-chap, which is why buy/reconnect/voucher/free-mode auto-connect never
            // actually worked for a real connecting device despite the RADIUS credentials being
            // valid the whole time.
            // radius-interim-update must be set explicitly — RouterOS's default (unset) means it
            // never sends an Interim-Update, so radacct.acctinputoctets/acctoutputoctets stay at
            // their session-start value (0) for the entire session and only become accurate once
            // the session actually ends. Every usage-based feature (FUP cap enforcement, the
            // "Data Usage" display) reads radacct live and was blind to real-time usage as a
            // result.
            $api->query('/ip/hotspot/profile/add', [
                'name' => $profileName,
                'hotspot-address' => $subnet['gateway'],
                'use-radius' => 'yes',
                'login-by' => 'cookie,http-chap,http-pap',
                'radius-interim-update' => '00:05:00',
            ]);
            // RouterOS defaults a new hotspot server to disabled=yes unless told otherwise —
            // without this it sits inactive after "successful" provisioning, silently accepting
            // no client traffic at all.
            // idle-timeout must be set explicitly — RouterOS defaults a new hotspot server to
            // 5m, meaning a customer who just walks away (WiFi off, out of range) without a
            // proper logout isn't detected as offline — and radacct.acctstoptime isn't set, so
            // the dashboard's online count and the Live column both keep showing them online —
            // for up to 5 minutes. 2m materially speeds up "went offline" detection without
            // being so aggressive it disconnects someone who's just idle on a page.
            $api->query('/ip/hotspot/add', [
                'name' => $serverName,
                'interface' => $interface,
                'address-pool' => $poolName,
                'profile' => $profileName,
                'disabled' => 'no',
                'idle-timeout' => '2m',
            ]);
        }

        // Runs every time, even when the server above already existed: a router provisioned
        // before this fix has a hotspot server but no DHCP server feeding its pool at all.
        // Confirmed live: the only DHCP server actually running was RouterOS's untouched
        // factory default, bound to this same interface but handing out a completely
        // different subnet (192.168.88.0/24 vs. the hotspot pool's 172.20.x.0/24) — so
        // clients landed in a range the hotspot never recognized as its own and the portal
        // never triggered. RouterOS doesn't sanely support two independent DHCP servers on one
        // interface, so an existing one is repointed at our pool rather than left running
        // alongside a new one.
        $existingDhcp = $api->queryWhere('/ip/dhcp-server/print', 'interface', $interface);
        if ($existingDhcp) {
            if (($existingDhcp[0]['address-pool'] ?? null) !== $poolName) {
                $api->setById('/ip/dhcp-server/set', $existingDhcp[0]['.id'], ['address-pool' => $poolName, 'lease-time' => '1h']);
            }
        } else {
            $api->query('/ip/dhcp-server/add', [
                'name' => "radiuspoint_hs_dhcp_{$interface}",
                'interface' => $interface,
                'address-pool' => $poolName,
                'lease-time' => '1h',
                'disabled' => 'no',
            ]);
        }

        return ['server' => $serverName, 'pool' => $poolName, 'profile' => $profileName];
    }

    /**
     * Real PPPoE server on this interface: its own client pool + a named profile (again never
     * "default") bound via /interface/pppoe-server/server/add. RADIUS delegation itself is a
     * router-wide toggle (/ppp/aaa, set once in savePorts()), not a per-profile property —
     * /ppp/profile/add rejects use-radius outright.
     */
    protected function provisionPppoe(MikrotikApiService $api, string $interface): array
    {
        $subnet = $api->allocateSubnet();
        $poolName = "radiuspoint_ppp_pool_{$interface}";
        $profileName = "radiuspoint_ppp_prof_{$interface}";

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
     * Step 1 of decommissioning: generates a 6-digit code, valid 5 minutes, sent to the
     * requesting user via in-app + push notification (see RouterDecommissionCode) — deleting a
     * router is destructive and irreversible (it also drops the router's RADIUS client row),
     * so a bare confirm() dialog isn't enough friction for an action this consequential.
     */
    public function requestDecommission(Router $router)
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($this->decommissionCacheKey($router), $code, now()->addMinutes(5));

        Notification::send([Auth::user()], new RouterDecommissionCode($router, $code));

        return response()->json(['message' => 'A removal code was sent to your notifications.']);
    }

    /**
     * Step 2: verifies the code from requestDecommission() before actually deleting anything.
     */
    public function confirmDecommission(Request $request, Router $router)
    {
        $request->validate(['code' => 'required|string']);

        $key = $this->decommissionCacheKey($router);
        $expected = Cache::get($key);

        if (! $expected || ! hash_equals($expected, $request->code)) {
            return response()->json(['message' => 'That code is incorrect or has expired. Request a new one.'], 422);
        }

        Cache::forget($key);

        // In a true SaaS, you might want to check if there are active users on this router first!
        // Remove the matching RADIUS client row now — the WireGuard peer itself is removed by
        // ReconcileNetworking's next run, which diffs live peers against still-existing routers.
        DB::table('nas')->where('nasname', $router->ip_address)->delete();

        $router->delete();

        return response()->json(['message' => 'Hardware decommissioned and removed from network.', 'redirect' => route('routers.index')]);
    }

    private function decommissionCacheKey(Router $router): string
    {
        return "router-decommission-code-{$router->id}-".Auth::id();
    }

    /**
     * Live Monitor page shell. Each tab's data loads lazily via its own AJAX call below rather
     * than all upfront — real-world router links can be slow or flaky, so firing one request per
     * tab on demand is both faster to first paint and more resilient to a single slow call.
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
     * flag makes it return exactly one snapshot instead, so the "live" chart is really the
     * frontend polling this endpoint on a timer rather than a true persistent stream — far
     * simpler and fits the same connect-per-request pattern as every other Live Monitor tab.
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
     * character. Scrub every string value before it ever reaches response()->json().
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
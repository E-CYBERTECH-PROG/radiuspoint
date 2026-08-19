<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterTerminalLog;
use App\Notifications\RouterDecommissionCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
     * load. Each card has its own "Refresh Now" for an on-demand check.
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
        ]);

        // VPN subnet (10.0.0.0/24) is shared across all tenants — scan every router
        // regardless of tenant and pick the first free address, not derived from IDs.
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
        $slug = Str::slug(Auth::user()->tenant->company_name) ?: 'tenant';

        $router = Router::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'ip_address' => $ipAddress,
            'api_username' => "{$slug}_adm_" . Str::random(5),
            'api_password' => Str::random(16),
            'status' => 'pending',
            'public_token' => Str::random(24),
            'routeros_version' => $request->routeros_version,
            'board_model' => 'other',
            'secret_key' => $secretKey,
            'wg_public_key' => $wireguard['public'],
            'wg_private_key' => $wireguard['private'],
        ]);

        // Register this router as a RADIUS client — FreeRADIUS picks it up automatically.
        // The WireGuard peer itself is applied later by ReconcileNetworking.
        DB::table('nas')->insert([
            'nasname' => $ipAddress,
            'shortname' => $router->name,
            'type' => 'other',
            'ports' => 3799,
            'secret' => $secretKey,
            'description' => "RadiusPoint router #{$router->id}",
        ]);

        // Winbox/Web access ports, forwarded via nginx stream{} through the WireGuard
        // tunnel — deterministic from the router's own id.
        $router->update([
            'web_proxy_port' => 20000 + $router->id,
            'winbox_proxy_port' => 21000 + $router->id,
        ]);

        // Applying the WireGuard peer is normally left to the scheduled
        // router:reconcile-networking run (every minute) — but a fast admin can reach the
        // Provision page, paste the script, and click "Execute Uplink Handshake" well inside
        // that window, before the peer exists server-side at all. Nothing on the router is
        // wrong at that point; the server just hasn't caught up yet, and it reads as "hardware
        // unreachable" with no indication that waiting would fix it. Applying it synchronously
        // here removes that race entirely rather than making the admin guess whether to wait.
        try {
            Artisan::call('router:reconcile-networking');
        } catch (Exception $e) {
            Log::warning("Immediate WireGuard peer reconciliation failed for new router #{$router->id}: " . $e->getMessage());
        }

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

        // Pings a raw IP, not a hostname — RouterOS's :ping throws a hard "resolve failed"
        // script error (aborting everything below it, tunnel setup included) rather than just
        // evaluating false when a hostname won't resolve. DNS is still configured on the next
        // line for the /tool fetch further down, which does need to resolve our own domain.
        $script = "/ip dns set servers=8.8.8.8,8.8.4.4;\n" .
                  ":global rpDiag \"RadiusPoint: router has no internet - check the WAN port is plugged in, has a valid IP, and the router has a valid default route.\";\n" .
                  "if ([:ping 8.8.8.8 count=3]) do={\n" .
                  // Remove any existing startup.rsc first — /tool fetch won't overwrite it, and
                  // a stale copy would keep getting re-imported on every retry.
                  "  :if ([:len [/file find name=startup.rsc]] > 0) do={ /file remove startup.rsc };\n" .
                  // check-certificate=no: a fresh router's clock isn't set yet, so it can
                  // reject a valid cert as expired. The tunnel set up moments later is what
                  // actually authenticates the router going forward.
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

            // Longer timeout — a freshly-booted router's API service can still be settling
            // right after provisioning.
            if ($api->connect($router->ip_address, $router->api_username, $router->api_password, timeout: 8)) {
                $update = ['status' => 'provisioning', 'last_seen' => now()];

                // First successful reach of this hardware — auto-fill what was left as a
                // placeholder at add-time, now that the router itself can answer for it.
                if (! $router->board_model || $router->board_model === 'other') {
                    $resource = $api->query('/system/resource/print');
                    if ($detected = $this->detectBoardModel($resource[0]['board-name'] ?? null)) {
                        $update['board_model'] = $detected;
                    }

                    // "MikroTik" is RouterOS's classic factory default. Some newer builds
                    // instead default the identity to the board's own internal board-name
                    // (confirmed live: an untouched router reported identity "L009UiGS",
                    // identical to its board-name) — equally not a real identity, so that's
                    // excluded too rather than overwriting the admin's placeholder with it.
                    $identity = $api->query('/system/identity/print');
                    $realName = $identity[0]['name'] ?? null;
                    $boardName = $resource[0]['board-name'] ?? null;
                    if ($realName && $realName !== 'MikroTik' && strcasecmp($realName, (string) $boardName) !== 0) {
                        $update['name'] = $realName;
                    }
                }

                $router->update($update);
                $this->enableRadiusOnDefaultProfiles($api, $router);

                return response()->json(['status' => 'success', 'message' => 'Uplink Established!']);
            }

            return response()->json(['status' => 'error', 'message' => 'Hardware unreachable. Ensure script was pasted.'], 400);

        } catch (Exception $e) {
            Log::error("MikroTik Connection Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Network timeout. Try again.'], 500);
        }
    }

    /**
     * Enables RADIUS auth on the default Hotspot profile and globally for PPP. Best-effort —
     * a fresh router may not have Hotspot configured yet.
     */
    public function enableRadiusOnDefaultProfiles(MikrotikApiService $api, Router $router): void
    {
        $slug = $router->namingSlug();

        try {
            $id = $api->findId('/ip/hotspot/profile/print', 'name', 'default');

            if ($id) {
                $api->setById('/ip/hotspot/profile/set', $id, ['use-radius' => 'yes']);
            }
        } catch (Exception $e) {
            Log::warning("Could not enable RADIUS on the default Hotspot profile: " . $e->getMessage());
        }

        // PPP's RADIUS delegation is a router-wide toggle, not per-profile — /ppp/profile/set
        // rejects use-radius. interim-update matches Hotspot's, so radacct usage doesn't
        // freeze at 0 until disconnect.
        try {
            $api->query('/ppp/aaa/set', ['use-radius' => 'yes', 'interim-update' => '00:05:00']);
        } catch (Exception $e) {
            Log::warning("Could not enable RADIUS on /ppp/aaa: " . $e->getMessage());
        }

        // Defense-in-depth: RouterOS doesn't re-check RADIUS mid-session, so deleting the
        // credential alone doesn't cut off someone already connected. ExpireOverdueUsers adds
        // their IP to {slug}-expired, which this rule then drops. Migrates a rule from either
        // earlier naming generation (platform-wide, or the original short-lived name) in place.
        try {
            foreach (['rp-expired-block', 'radiuspoint-expired-block'] as $oldComment) {
                $oldId = $api->findId('/ip/firewall/filter/print', 'comment', $oldComment);
                if ($oldId) {
                    $api->setById('/ip/firewall/filter/set', $oldId, [
                        'src-address-list' => "{$slug}-expired",
                        'comment' => "{$slug}-expired-block",
                    ]);
                }
            }

            $dropRuleId = $api->findId('/ip/firewall/filter/print', 'comment', "{$slug}-expired-block");
            if (! $dropRuleId) {
                $api->query('/ip/firewall/filter/add', [
                    'chain' => 'forward',
                    'action' => 'drop',
                    'src-address-list' => "{$slug}-expired",
                    'comment' => "{$slug}-expired-block",
                    'place-before' => '0',
                ]);
            }
        } catch (Exception $e) {
            Log::warning("Could not add the {$slug}-expired firewall rule: " . $e->getMessage());
        }

        // RouterOS's default fasttrack rule skips further firewall/queue processing for a
        // connection's lifetime, which can bypass both the expired-block rule above and FUP
        // throttling. Disabled (not removed) rather than left on.
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
     * Saves captive-portal customization for this router. Takes effect immediately — the
     * portal page is served live, no re-provisioning needed.
     */
    public function updateCaptivePortal(Request $request, Router $router)
    {
        $request->validate([
            'template' => 'required|in:light-lumen,crystal,grid,package,raw,cyberpunk,lipa',
            'logo_url' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|max:20',
            'columns_per_row' => 'nullable|integer|in:1,2,3,4',
            'show_speed' => 'nullable|boolean',
            'show_navbar' => 'nullable|boolean',
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
                'columns_per_row' => $request->columns_per_row ?: null,
                'show_speed' => $request->boolean('show_speed'),
                'show_navbar' => $request->boolean('show_navbar'),
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
     * Health-check for an already-configured router. Unlike checkStatus() (ZTP-specific),
     * returns live system info for the UI.
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
            $this->enableRadiusOnDefaultProfiles($api, $router);

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
     * Stands up a Hotspot and/or PPPoE server on each interface the admin assigned one to.
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

        // Two checkboxes per interface (Hotspot/PPPoE) collapsed into the role string the rest
        // of this method expects. all_interfaces[] includes unchecked ones too, so switching
        // back to "none" is still recorded.
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

        // PPP's RADIUS delegation is a router-wide toggle, not per-profile (/ppp/profile/add
        // rejects use-radius). Set once here before the per-interface loop.
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
                    $interfaceResult['hotspot'] = $this->provisionHotspot($api, $interface, $router);
                } catch (Exception $e) {
                    Log::warning("Hotspot provisioning failed for {$router->name}/{$interface}: " . $e->getMessage());
                    $interfaceResult['hotspot_error'] = $e->getMessage();
                }
            }

            if (in_array($role, ['pppoe', 'both'], true)) {
                try {
                    $interfaceResult['pppoe'] = $this->provisionPppoe($api, $interface, $router);
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
     * Adds a walled-garden rule so unauthenticated clients can reach the portal, and rewrites
     * the router's local login.html to redirect there with RouterOS's template variables as
     * query params. Idempotent — also used by pushCaptivePortalFiles().
     */
    protected function provisionCaptivePortal(MikrotikApiService $api, Router $router): array
    {
        $slug = $router->namingSlug();
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        $existingRule = $api->findId('/ip/hotspot/walled-garden/print', 'dst-host', $host);
        if (! $existingRule) {
            $api->query('/ip/hotspot/walled-garden/add', [
                'action' => 'allow',
                'dst-host' => $host,
                'comment' => "{$slug} captive portal",
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
        // added. Only touches profiles we created (current or an older naming generation) —
        // never an admin's own. Migrates any older generation to today's tenant-branded name.
        $loginByFixed = 0;
        $interimUpdateFixed = 0;
        $renamed = 0;
        $profiles = $api->query('/ip/hotspot/profile/print');
        foreach ($profiles as $profile) {
            $name = $profile['name'] ?? '';
            $oldPrefix = match (true) {
                str_starts_with($name, "{$slug}_hs_prof_") => null,
                str_starts_with($name, 'radiuspoint_hs_prof_') => 'radiuspoint_hs_prof_',
                str_starts_with($name, 'rp_hs_prof_') => 'rp_hs_prof_',
                default => false,
            };
            if ($oldPrefix === false) {
                continue;
            }
            if ($oldPrefix !== null) {
                $name = "{$slug}_hs_prof_".substr($name, strlen($oldPrefix));
                $api->setById('/ip/hotspot/profile/set', $profile['.id'], ['name' => $name]);
                $renamed++;
            }
            // Strip "cookie" (RouterOS's own opaque per-device auto-relogin cache, which
            // can re-authenticate a device with stale attributes and bypasses our own
            // MAC-based reconnect flow entirely) and ensure http-pap is present.
            $current = $profile['login-by'] ?? '';
            $methods = array_filter(explode(',', $current), fn ($m) => $m !== '' && $m !== 'cookie');
            if (! in_array('http-pap', $methods, true)) {
                $methods[] = 'http-pap';
            }
            $desired = implode(',', $methods);
            if ($desired !== $current) {
                $api->setById('/ip/hotspot/profile/set', $profile['.id'], ['login-by' => $desired]);
                $loginByFixed++;
            }
            // RouterOS reads this back as "5m", never the "00:05:00" form used to set it —
            // comparing against "00:05:00" would rewrite the same value on every push.
            if (($profile['radius-interim-update'] ?? '') !== '5m') {
                $api->setById('/ip/hotspot/profile/set', $profile['.id'], [
                    'radius-interim-update' => '00:05:00',
                ]);
                $interimUpdateFixed++;
            }
        }

        // Retroactive fix for hotspot servers missing an explicit idle-timeout. Same
        // recognize-current-and-older-generations approach as the profile loop above.
        $idleTimeoutFixed = 0;
        $servers = $api->query('/ip/hotspot/print');
        foreach ($servers as $server) {
            $name = $server['name'] ?? '';
            $oldPrefix = match (true) {
                str_starts_with($name, "{$slug}_hotspot_") => null,
                str_starts_with($name, 'radiuspoint_hs_server_') => 'radiuspoint_hs_server_',
                str_starts_with($name, 'rp_hs_') => 'rp_hs_',
                default => false,
            };
            if ($oldPrefix === false) {
                continue;
            }
            if ($oldPrefix !== null) {
                $name = "{$slug}_hotspot_".substr($name, strlen($oldPrefix));
                $api->setById('/ip/hotspot/set', $server['.id'], ['name' => $name]);
                $renamed++;
            }
            if (($server['idle-timeout'] ?? '') !== '2m') {
                $api->setById('/ip/hotspot/set', $server['.id'], ['idle-timeout' => '2m']);
                $idleTimeoutFixed++;
            }
        }

        // Renames older-generation address pools to the current tenant-branded naming.
        $pools = $api->query('/ip/pool/print');
        foreach ($pools as $pool) {
            $name = $pool['name'] ?? '';
            if (str_starts_with($name, "{$slug}_hs_pool_") || str_starts_with($name, "{$slug}_pppoe_pool_")) {
                continue;
            }
            if (str_starts_with($name, 'rp_hs_') && ! str_starts_with($name, 'rp_hs_prof_')) {
                $newName = "{$slug}_hs_pool_".substr($name, strlen('rp_hs_'));
            } elseif (str_starts_with($name, 'radiuspoint_hs_pool_')) {
                $newName = "{$slug}_hs_pool_".substr($name, strlen('radiuspoint_hs_pool_'));
            } elseif (str_starts_with($name, 'rp_ppp_') && ! str_starts_with($name, 'rp_ppp_prof_')) {
                $newName = "{$slug}_pppoe_pool_".substr($name, strlen('rp_ppp_'));
            } elseif (str_starts_with($name, 'radiuspoint_ppp_pool_')) {
                $newName = "{$slug}_pppoe_pool_".substr($name, strlen('radiuspoint_ppp_pool_'));
            } else {
                continue;
            }
            $api->setById('/ip/pool/set', $pool['.id'], ['name' => $newName]);
            $renamed++;
        }

        // Same rename for PPPoE profiles.
        $pppProfiles = $api->query('/ppp/profile/print');
        foreach ($pppProfiles as $profile) {
            $name = $profile['name'] ?? '';
            if (str_starts_with($name, "{$slug}_pppoe_prof_")) {
                continue;
            }
            if (str_starts_with($name, 'rp_ppp_prof_')) {
                $newName = "{$slug}_pppoe_prof_".substr($name, strlen('rp_ppp_prof_'));
            } elseif (str_starts_with($name, 'radiuspoint_ppp_prof_')) {
                $newName = "{$slug}_pppoe_prof_".substr($name, strlen('radiuspoint_ppp_prof_'));
            } else {
                continue;
            }
            $api->setById('/ppp/profile/set', $profile['.id'], ['name' => $newName]);
            $renamed++;
        }

        // Retroactive fix for PPPoE servers provisioned before one-session-per-host was added
        // — only ones pointed at a profile we created (current or an older naming generation),
        // never an admin's own. Without it, one customer's credentials can hold multiple
        // simultaneous sessions from different devices.
        $oneSessionFixed = 0;
        $pppoeServers = $api->query('/interface/pppoe-server/server/print');
        foreach ($pppoeServers as $server) {
            $profileName = $server['default-profile'] ?? '';
            $isOurs = str_starts_with($profileName, "{$slug}_pppoe_prof_")
                || str_starts_with($profileName, 'rp_ppp_prof_')
                || str_starts_with($profileName, 'radiuspoint_ppp_prof_');
            if (! $isOurs) {
                continue;
            }
            // RouterOS accepts yes/no on write but always reports this field back as
            // true/false on read — comparing against 'yes' would never match and re-apply
            // the same (harmless but noisy) fix on every single push.
            if (($server['one-session-per-host'] ?? '') !== 'true') {
                $api->setById('/interface/pppoe-server/server/set', $server['.id'], ['one-session-per-host' => 'yes']);
                $oneSessionFixed++;
            }
        }

        // Retroactive fix: an older provisioning script pointed /radius at the server's public
        // IP instead of the tunnel-internal address FreeRADIUS binds to, and re-provisioning
        // could leave stale duplicate entries with an outdated secret. Scoped to
        // service=hotspot,ppp so it never touches a /radius client an admin configured for
        // something else.
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
            'one_session_per_host_fixed' => $oneSessionFixed,
        ];
    }

    /**
     * Free Mode: throttles a session to a low rate cap via Mikrotik-Rate-Limit and tags it
     * into an address-list via Mikrotik-Group, restricted to a small set of allowed domains by
     * the walled-garden firewall rules below. Idempotent — safe to call on every push.
     */
    protected function provisionFreeMode(MikrotikApiService $api, Router $router): array
    {
        $slug = $router->namingSlug();
        $profileName = "{$slug}_free";
        $freemodeList = "{$slug}-freemode";
        $allowedList = "{$slug}-freemode-allowed";

        // Migrate an older-generation profile in place, including its address-list name.
        foreach (['rp_free', 'radiuspoint_free'] as $oldName) {
            $oldProfileId = $api->findId('/ip/hotspot/user/profile/print', 'name', $oldName);
            if ($oldProfileId) {
                $api->setById('/ip/hotspot/user/profile/set', $oldProfileId, [
                    'name' => $profileName,
                    'address-list' => $freemodeList,
                ]);
            }
        }

        $profileId = $api->findId('/ip/hotspot/user/profile/print', 'name', $profileName);
        if (! $profileId) {
            $api->query('/ip/hotspot/user/profile/add', [
                'name' => $profileName,
                'address-list' => $freemodeList,
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
                // Keep its address-list pointed at the current name.
                $api->setById('/ip/dns/static/set', $existing, ['address-list' => $allowedList]);
            } else {
                $api->query('/ip/dns/static/add', [
                    'regexp' => $regexp,
                    'type' => 'FWD',
                    'forward-to' => '8.8.8.8',
                    'address-list' => $allowedList,
                    'ttl' => '1m',
                    'comment' => "{$slug} free mode",
                ]);
                $dnsAdded++;
            }
        }

        // Same migrate-in-place approach for the two firewall rules.
        foreach (['rp-freemode-allow', 'radiuspoint-freemode-allow'] as $oldComment) {
            $oldAcceptRuleId = $api->findId('/ip/firewall/filter/print', 'comment', $oldComment);
            if ($oldAcceptRuleId) {
                $api->setById('/ip/firewall/filter/set', $oldAcceptRuleId, [
                    'src-address-list' => $freemodeList,
                    'dst-address-list' => $allowedList,
                    'comment' => "{$slug}-freemode-allow",
                ]);
            }
        }

        $acceptRuleId = $api->findId('/ip/firewall/filter/print', 'comment', "{$slug}-freemode-allow");
        if (! $acceptRuleId) {
            $api->query('/ip/firewall/filter/add', [
                'chain' => 'forward',
                'action' => 'accept',
                'src-address-list' => $freemodeList,
                'dst-address-list' => $allowedList,
                'comment' => "{$slug}-freemode-allow",
            ]);
        }

        foreach (['rp-freemode-block-rest', 'radiuspoint-freemode-block-rest'] as $oldComment) {
            $oldDropRuleId = $api->findId('/ip/firewall/filter/print', 'comment', $oldComment);
            if ($oldDropRuleId) {
                $api->setById('/ip/firewall/filter/set', $oldDropRuleId, [
                    'src-address-list' => $freemodeList,
                    'comment' => "{$slug}-freemode-block-rest",
                ]);
            }
        }

        $dropRuleId = $api->findId('/ip/firewall/filter/print', 'comment', "{$slug}-freemode-block-rest");
        if (! $dropRuleId) {
            $api->query('/ip/firewall/filter/add', [
                'chain' => 'forward',
                'action' => 'drop',
                'src-address-list' => $freemodeList,
                'comment' => "{$slug}-freemode-block-rest",
            ]);
        }

        return [
            'profile' => $profileId ? 'already present' : 'added',
            'dns_rules_added' => $dnsAdded,
            'firewall_rules' => ($acceptRuleId && $dropRuleId) ? 'already present' : 'added',
        ];
    }

    /**
     * Manually re-runs the captive-portal + free-mode wiring for a router that's already
     * provisioned — e.g. after a factory reset.
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
                'one_session_per_host_fixed' => $result['one_session_per_host_fixed'],
                'free_mode_profile' => $freeMode['profile'],
                'free_mode_firewall' => $freeMode['firewall_rules'],
            ]);
        } catch (Exception $e) {
            Log::error("Captive portal push failed for {$router->name}: " . $e->getMessage());

            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Removes hotspot servers RouterOS marks invalid=yes (typically a bridge-port interface
     * issue), plus their orphaned profile/pool. Safe to call repeatedly.
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

    /**
     * Real Hotspot server on this interface: its own IP, address pool, and a named profile
     * (never "default") wired to authenticate via RADIUS.
     */
    protected function provisionHotspot(MikrotikApiService $api, string $interface, Router $router): array
    {
        // A hotspot server can't run on a bridge-port interface — resolve to the real bridge
        // first so every bridged port converges on the same server.
        $bridgePort = $api->queryWhere('/interface/bridge/port/print', 'interface', $interface);
        if ($bridgePort) {
            $interface = $bridgePort[0]['bridge'];
        }

        $slug = $router->namingSlug();
        $serverName = "{$slug}_hotspot_{$interface}";
        $poolName = "{$slug}_hs_pool_{$interface}";
        $profileName = "{$slug}_hs_prof_{$interface}";

        // Idempotent — skip if a server with this name already exists (RouterOS rejects
        // duplicate names).
        if (! $api->findId('/ip/hotspot/print', 'name', $serverName)) {
            $subnet = $api->allocateSubnet();

            $api->query('/ip/address/add', ['address' => "{$subnet['gateway']}/24", 'interface' => $interface]);
            $api->query('/ip/pool/add', ['name' => $poolName, 'ranges' => $subnet['range']]);
            // A DHCP server (added below) only assigns addresses from this pool — it doesn't by
            // itself supply a gateway/DNS for the subnet those addresses are in. Without a
            // matching /ip/dhcp-server/network entry, RouterOS has nothing to offer for those
            // fields, and a client leases an address it can't actually route anywhere with —
            // it never gets far enough to hit the hotspot's redirect at all, let alone the
            // portal itself. RouterOS's own factory-default network (192.168.88.0/24) is a
            // separate entry and is left alone.
            $api->query('/ip/dhcp-server/network/add', [
                'address' => $subnet['cidr'],
                'gateway' => $subnet['gateway'],
                'dns-server' => $subnet['gateway'],
            ]);
            // login-by must include http-pap — RouterOS's default (cookie,http-chap) silently
            // rejects the plain username+password POST our captive portal submits. "cookie" is
            // deliberately left out: it lets RouterOS silently re-authenticate a returning
            // device from its own cached browser cookie, bypassing our MAC-based reconnect
            // flow and re-using stale RADIUS attributes from the original login instead of
            // re-checking the customer's current plan.
            // radius-interim-update must be set explicitly, or radacct usage stays at 0 until
            // the session ends — breaking FUP enforcement and live usage display.
            $api->query('/ip/hotspot/profile/add', [
                'name' => $profileName,
                'hotspot-address' => $subnet['gateway'],
                'use-radius' => 'yes',
                'login-by' => 'http-chap,http-pap',
                'radius-interim-update' => '00:05:00',
            ]);
            // RouterOS defaults a new hotspot server to disabled=yes unless told otherwise.
            // idle-timeout set explicitly to 2m (RouterOS default 5m) — speeds up offline
            // detection for the dashboard's online count.
            $api->query('/ip/hotspot/add', [
                'name' => $serverName,
                'interface' => $interface,
                'address-pool' => $poolName,
                'profile' => $profileName,
                'disabled' => 'no',
                'idle-timeout' => '2m',
            ]);
        }

        // Runs every time, even for an existing server — a router provisioned before this fix
        // has no DHCP server feeding its hotspot pool. Repoints RouterOS's factory-default DHCP
        // server at our pool rather than running two on one interface.
        $existingDhcp = $api->queryWhere('/ip/dhcp-server/print', 'interface', $interface);
        if ($existingDhcp) {
            if (($existingDhcp[0]['address-pool'] ?? null) !== $poolName) {
                $api->setById('/ip/dhcp-server/set', $existingDhcp[0]['.id'], ['address-pool' => $poolName, 'lease-time' => '1h']);
            }
        } else {
            $api->query('/ip/dhcp-server/add', [
                'name' => "{$slug}_hs_dhcp_{$interface}",
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
    protected function provisionPppoe(MikrotikApiService $api, string $interface, Router $router): array
    {
        // Same reason as provisionHotspot(): RouterOS marks a PPPoE server bound directly to a
        // bridge-port interface invalid ("Service is on a slave interface") — it never actually
        // serves anything. Confirmed live: six bridge-slave ports mapped to "both" each got
        // their own invalid PPPoE server instead of converging on the bridge like hotspot does.
        $bridgePort = $api->queryWhere('/interface/bridge/port/print', 'interface', $interface);
        if ($bridgePort) {
            $interface = $bridgePort[0]['bridge'];
        }

        $slug = $router->namingSlug();
        $poolName = "{$slug}_pppoe_pool_{$interface}";
        $profileName = "{$slug}_pppoe_prof_{$interface}";

        // Idempotent, matching provisionHotspot() — re-submitting the port mapping form (a
        // second pass to adjust another interface, or just clicking Save again) otherwise hit
        // "failure: pool with such name exists" every time, since none of this checked for an
        // existing server first.
        $existing = $api->queryWhere('/interface/pppoe-server/server/print', 'interface', $interface);
        if ($existing) {
            return ['pool' => $poolName, 'profile' => $profileName];
        }

        $subnet = $api->allocateSubnet();

        $api->query('/ip/pool/add', ['name' => $poolName, 'ranges' => $subnet['range']]);
        $api->query('/ppp/profile/add', [
            'name' => $profileName,
            'remote-address' => $poolName,
        ]);
        $api->query('/interface/pppoe-server/server/add', [
            'interface' => $interface,
            'service-name' => $slug,
            'default-profile' => $profileName,
            'disabled' => 'no',
            // Without this, one customer's credentials can hold multiple simultaneous
            // sessions from different devices — lets an account get shared/resold.
            'one-session-per-host' => 'yes',
        ]);

        return ['pool' => $poolName, 'profile' => $profileName, 'cidr' => $subnet['cidr']];
    }

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
     * Matches RouterOS's reported board name against our catalog, so a router added as
     * "Other/Generic" gets its real specs filled in once reachable.
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
     * Step 1 of decommissioning: sends a 6-digit code (valid 5 minutes) via notification
     * before allowing deletion.
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

        // Remove the RADIUS client row now — the WireGuard peer is removed later by ReconcileNetworking.
        DB::table('nas')->where('nasname', $router->ip_address)->delete();

        $router->delete();

        return response()->json(['message' => 'Hardware decommissioned and removed from network.', 'redirect' => route('routers.index')]);
    }

    private function decommissionCacheKey(Router $router): string
    {
        return "router-decommission-code-{$router->id}-".Auth::id();
    }

    /**
     * Live Monitor page shell. Each tab loads lazily via its own AJAX call for a faster first paint.
     */
    public function monitor(Router $router)
    {
        return view('routers.monitor', compact('router'));
    }

    public function monitorLogs(Router $router)
    {
        // /log/print has no server-side "last N" filter — cap to the most recent 150 client-side.
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
     * Powers the Traffic tab's dropdown — uses /interface/print, not the Ethernet-only
     * endpoint, so any interface type can be watched.
     */
    public function monitorInterfaceList(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/interface/print'));
    }

    /**
     * /interface/monitor-traffic normally streams continuously; the "once" flag returns a
     * single snapshot instead, so the "live" chart is really the frontend polling this on a timer.
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
     * Some RouterOS v7 builds move this to /interface/wifi/registration-table — let the "no
     * such command" error surface naturally.
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
     * /tool/torch has no "once" flag, but duration= bounds the call the same way — blocks for
     * that long then returns aggregated rows.
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
     * Feeds the CPU/Memory History chart — separate from testConnection() so this doesn't also
     * write status/last_seen on every poll.
     */
    public function monitorResource(Router $router)
    {
        return $this->monitorData($router, fn ($api) => $api->query('/system/resource/print'));
    }

    /**
     * RouterOS drops the connection on reboot without ever sending !done — the resulting
     * StreamException is expected, not a failure.
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
     * Blocks via a Hotspot IP binding (type=blocked) — the RouterOS-native way to deny a client.
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
            'comment' => 'Blocked via '.$router->namingSlug(),
        ]), 'Address blocked.');
    }

    /**
     * Paths a console command can never target — irreversible on live hardware, or manage the
     * router's own auth.
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
     * Parses one RouterOS API command line (endpoint + key=value attributes) and runs it via
     * the binary API. Every attempt is logged regardless of outcome.
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
     * Shared connect + action + JSON-response plumbing for Live Monitor's remote-control buttons.
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
     * Shared connect + query + JSON-response plumbing for every Live Monitor tab.
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
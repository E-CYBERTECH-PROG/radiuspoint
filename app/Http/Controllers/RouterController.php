<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\MikrotikApiService;
use Exception;
use Illuminate\Support\Facades\Log;

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

        // Secure Auto-IP Allocation (Prevents overlapping IPs)
        $lastRouter = Router::orderBy('id', 'desc')->first();
        $nextIpPart = $lastRouter ? ($lastRouter->id + 2) : 2; 
        
        // Ensure we don't exceed the /24 subnet limit (254)
        if ($nextIpPart > 254) {
            return back()->with('error', 'VPN Subnet exhausted. Contact SuperAdmin.');
        }

        $ipAddress = "10.0.0." . $nextIpPart;

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
        ]);

        return redirect()->route('routers.provision', $router->id);
    }

    /**
     * Step 2: Generate the High-Tech Provisioning Script.
     */
    public function provision(Router $router)
    {
        // We generate a unique RADIUS secret for this specific router
        if (!$router->secret_key) {
            $router->update(['secret_key' => Str::random(12)]);
        }

        // Tunnel setup differs by RouterOS version — WireGuard is v7-only; v6 uses an L2TP/IPsec tunnel instead.
        if ($router->routeros_version === 'v6') {
            $tunnel = "/interface l2tp-client add name=l2tp-isp connect-to=YOUR_SERVER_PUBLIC_IP user={$router->api_username} password={$router->api_password} ipsec-secret={$router->secret_key} use-ipsec=yes disabled=no; " .
                      "/ip address add address={$router->ip_address}/24 interface=l2tp-isp; ";
        } else {
            $tunnel = "/interface wireguard add name=wg-isp listen-port=13231; " .
                      "/interface wireguard peers add interface=wg-isp public-key=\"YOUR_SERVER_PUBLIC_KEY\" endpoint-address=\"YOUR_SERVER_PUBLIC_IP\" allowed-address=0.0.0.0/0; " .
                      "/ip address add address={$router->ip_address}/24 interface=wg-isp; ";
        }

        // The "Beast Mode" ZTP Script (Now with RADIUS Authentication forced on)
        $script = $tunnel .
                  "/user add name={$router->api_username} password={$router->api_password} group=full; " .
                  "/ip service set api disabled=no port=8728; " .

                  // -- RADIUS INTEGRATION WIRING --
                  "/radius add address=YOUR_SERVER_PUBLIC_IP secret={$router->secret_key} service=hotspot,ppp; " .
                  "/radius incoming set accept=yes port=3799; " .

                  // Force Hotspot to use RADIUS
                  "/ip hotspot profile set [find default=yes] use-radius=yes; " .

                  // Force PPPoE to use RADIUS
                  "/ppp profile set [find default=yes] use-radius=yes;";

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
                $router->update([
                    'status' => 'provisioning',
                    'last_seen' => now()
                ]);
                return response()->json(['status' => 'success', 'message' => 'Uplink Established!']);
            }

            return response()->json(['status' => 'error', 'message' => 'Hardware unreachable. Ensure script was pasted.'], 400);
            
        } catch (Exception $e) {
            Log::error("MikroTik Connection Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Network timeout. Try again.'], 500);
        }
    }

    /**
     * Detail view: board diagram, live stats, Web/Winbox access, rename/model form.
     */
    public function show(Router $router)
    {
        $models = config('mikrotik_models');

        return view('routers.show', compact('router', 'models'));
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

            $router->update(['status' => 'active', 'last_seen' => now()]);

            return response()->json([
                'status' => 'online',
                'identity' => $identity[0]['name'] ?? null,
                'board_model_detected' => $resource[0]['board-name'] ?? null,
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
     * FINAL STEP: Save the Interface configuration.
     */
    public function savePorts(Request $request, Router $router)
    {
        $router->update([
            'port_configuration' => $request->ports,
            'status' => 'active'
        ]);

        return redirect()->route('routers.index')->with('success', 'Hardware Bridge fully operational!');
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
     * Decommission Hardware (Secure Deletion).
     */
    public function destroy(Router $router)
    {
        // In a true SaaS, you might want to check if there are active users on this router first!
        $router->delete();
        
        return redirect()->route('routers.index')->with('success', 'Hardware decommissioned and removed from network.');
    }
}
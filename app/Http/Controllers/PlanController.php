<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\MikrotikApiService;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    // Display all plans
    public function index(Request $request)
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('plans.index', compact('plans'));
    }

    // Show the "Create Plan" form
    public function create()
    {
        return view('plans.create');
    }

    // Save the plan and push it to the routers
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => 'required|in:minutes,hours,days,weeks,months',
            'data_cap_mb' => 'nullable|integer|min:1',
            'speed_limit' => 'required|string', // e.g., 5M/5M
        ]);

        // 1. Save to Central Cloud Database
        $plan = Plan::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'data_cap_mb' => $request->data_cap_mb,
            'speed_limit' => $request->speed_limit,
        ]);

        $this->syncToRouters($plan);

        return redirect()->route('plans.index')->with('success', 'Plan created and synced to hardware successfully!');
    }

    public function edit(Plan $plan)
    {
        return view('plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => 'required|in:minutes,hours,days,weeks,months',
            'data_cap_mb' => 'nullable|integer|min:1',
            'speed_limit' => 'required|string',
        ]);

        $plan->update([
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'data_cap_mb' => $request->data_cap_mb,
            'speed_limit' => $request->speed_limit,
        ]);

        $this->syncToRouters($plan);

        return redirect()->route('plans.index')->with('success', 'Plan updated and re-synced to hardware successfully!');
    }

    public function destroy(Plan $plan)
    {
        $inUse = PppoeUser::where('current_plan_id', $plan->id)->exists()
            || HotspotUser::where('current_plan_id', $plan->id)->exists();

        if ($inUse) {
            return redirect()->route('plans.index')->with('error', 'Cannot delete this plan — it is still assigned to one or more customers.');
        }

        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plan removed.');
    }

    // Multi-Router Sync: push this profile to all active routers owned by this ISP
    private function syncToRouters(Plan $plan): void
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)
                         ->where('status', 'active')
                         ->get();

        $api = new MikrotikApiService();

        foreach ($routers as $router) {
            if ($api->connect($router->ip_address, $router->api_username, $router->api_password)) {

                // Route the command based on whether it is Hotspot or PPPoE
                if ($plan->type === 'hotspot') {
                    $api->query('/ip/hotspot/user/profile/add', [
                        'name' => $plan->name,
                        'rate-limit' => $plan->speed_limit,
                        'shared-users' => '1',
                        'transparent-proxy' => 'yes'
                    ]);
                } else {
                    $api->query('/ppp/profile/add', [
                        'name' => $plan->name,
                        'rate-limit' => $plan->speed_limit,
                        'only-one' => 'yes'
                    ]);
                }
            } else {
                Log::warning("Failed to sync Plan {$plan->name} to Router {$router->name}");
            }
        }
    }
}
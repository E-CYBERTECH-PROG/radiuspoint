<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Services\RadiusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HotspotUserController extends Controller
{
    public function index(Request $request)
    {
        $users = HotspotUser::where('tenant_id', Auth::user()->tenant_id)
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('phone_number', 'like', "%{$request->search}%")->orWhere('mac_address', 'like', "%{$request->search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('plan_id'), fn ($q) => $q->where('current_plan_id', $request->plan_id))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->get()->keyBy('id');
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get()->keyBy('id');

        return view('hotspot-users.index', compact('users', 'plans', 'routers'));
    }

    public function create()
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'hotspot')->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();

        return view('hotspot-users.create', compact('plans', 'routers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'mac_address' => 'nullable|string|max:255',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => 'required|in:active,expired,offline',
            'expires_at' => 'nullable|date',
        ]);

        $user = HotspotUser::create([
            'tenant_id' => Auth::user()->tenant_id,
            'phone_number' => $request->phone_number,
            'mac_address' => $request->mac_address,
            'current_plan_id' => $request->current_plan_id,
            'current_router_id' => $request->current_router_id,
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        if ($user->status === 'active') {
            $plan = Plan::find($user->current_plan_id);
            RadiusSyncService::sync($user->phone_number, Str::password(10), $plan?->speed_limit);
        }

        return redirect()->route('hotspot-users.index')->with('success', 'Hotspot customer added successfully.');
    }

    public function edit(HotspotUser $hotspot_user)
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'hotspot')->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();

        return view('hotspot-users.edit', compact("hotspot_user", 'plans', 'routers'));
    }

    public function update(Request $request, HotspotUser $hotspot_user)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'mac_address' => 'nullable|string|max:255',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => 'required|in:active,expired,offline',
            'expires_at' => 'nullable|date',
        ]);

        $hotspot_user->update([
            'phone_number' => $request->phone_number,
            'mac_address' => $request->mac_address,
            'current_plan_id' => $request->current_plan_id,
            'current_router_id' => $request->current_router_id,
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        if ($hotspot_user->status === 'active') {
            $plan = Plan::find($hotspot_user->current_plan_id);
            if (RadiusSyncService::hasCredential($hotspot_user->phone_number)) {
                // Preserve the existing password — only refresh the bandwidth profile.
                RadiusSyncService::updateRateLimit($hotspot_user->phone_number, $plan?->speed_limit);
            } else {
                RadiusSyncService::sync($hotspot_user->phone_number, Str::password(10), $plan?->speed_limit);
            }
        } else {
            RadiusSyncService::remove($hotspot_user->phone_number);
        }

        return redirect()->route('hotspot-users.index')->with('success', 'Hotspot customer updated successfully.');
    }

    public function destroy(HotspotUser $hotspot_user)
    {
        RadiusSyncService::remove($hotspot_user->phone_number);
        $hotspot_user->delete();

        return redirect()->route('hotspot-users.index')->with('success', 'Hotspot customer removed.');
    }
}

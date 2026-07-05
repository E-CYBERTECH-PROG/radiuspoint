<?php

namespace App\Http\Controllers;

use App\Models\PppoeUser;
use App\Models\Plan;
use App\Models\Router;
use App\Services\RadiusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PppoeUserController extends Controller
{
    public function index(Request $request)
    {
        $users = PppoeUser::where('tenant_id', Auth::user()->tenant_id)
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('username', 'like', "%{$request->search}%")->orWhere('phone_number', 'like', "%{$request->search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('plan_id'), fn ($q) => $q->where('current_plan_id', $request->plan_id))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->get()->keyBy('id');
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get()->keyBy('id');

        return view('pppoe-users.index', compact('users', 'plans', 'routers'));
    }

    public function create()
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'pppoe')->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();

        return view('pppoe-users.create', compact('plans', 'routers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => [
                'required', 'string', 'max:255',
                Rule::unique('pppoe_users')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => 'required|in:active,expired,offline',
            'expires_at' => 'nullable|date',
        ]);

        $user = PppoeUser::create([
            'tenant_id' => Auth::user()->tenant_id,
            'username' => $request->username,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'current_plan_id' => $request->current_plan_id,
            'current_router_id' => $request->current_router_id,
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        if ($user->status === 'active') {
            $plan = Plan::find($user->current_plan_id);
            RadiusSyncService::sync($user->username, Str::password(10), $plan?->speed_limit);
        }

        return redirect()->route('pppoe-users.index')->with('success', 'PPPoE customer added successfully.');
    }

    public function edit(PppoeUser $pppoe_user)
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'pppoe')->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();

        return view('pppoe-users.edit', compact("pppoe_user", 'plans', 'routers'));
    }

    public function update(Request $request, PppoeUser $pppoe_user)
    {
        $request->validate([
            'username' => [
                'required', 'string', 'max:255',
                Rule::unique('pppoe_users')->where('tenant_id', Auth::user()->tenant_id)->ignore($pppoe_user->id),
            ],
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => 'required|in:active,expired,offline',
            'expires_at' => 'nullable|date',
        ]);

        $pppoe_user->update([
            'username' => $request->username,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'current_plan_id' => $request->current_plan_id,
            'current_router_id' => $request->current_router_id,
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        if ($pppoe_user->status === 'active') {
            $plan = Plan::find($pppoe_user->current_plan_id);
            if (RadiusSyncService::hasCredential($pppoe_user->username)) {
                RadiusSyncService::updateRateLimit($pppoe_user->username, $plan?->speed_limit);
            } else {
                RadiusSyncService::sync($pppoe_user->username, Str::password(10), $plan?->speed_limit);
            }
        } else {
            RadiusSyncService::remove($pppoe_user->username);
        }

        return redirect()->route('pppoe-users.index')->with('success', 'PPPoE customer updated successfully.');
    }

    public function destroy(PppoeUser $pppoe_user)
    {
        RadiusSyncService::remove($pppoe_user->username);
        $pppoe_user->delete();

        return redirect()->route('pppoe-users.index')->with('success', 'PPPoE customer removed.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PlanRouterSync;
use App\Models\PppoeUser;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $activeRouterCount = Router::where('tenant_id', Auth::user()->tenant_id)->where('status', 'active')->count();

        // One aggregate query for sync status counts across all plans on the page.
        $syncCounts = PlanRouterSync::whereIn('plan_id', $plans->pluck('id'))
            ->selectRaw('plan_id, status, count(*) as c')
            ->groupBy('plan_id', 'status')
            ->get()
            ->groupBy('plan_id')
            ->map(fn ($rows) => $rows->pluck('c', 'status'));

        return view('plans.index', compact('plans', 'activeRouterCount', 'syncCounts'));
    }

    public function create()
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();

        return view('plans.create', compact('routers'));
    }

    // Router sync happens separately via the plan:reconcile scheduled command.
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => ['required', Rule::in(Plan::DURATION_UNITS)],
            'data_cap_mb' => 'nullable|integer|min:1',
            // Mikrotik-Rate-Limit format is rx-rate/tx-rate (e.g. "5M/5M"); RouterOS silently
            // ignores a value it can't parse, so an invalid separator leaves the plan uncapped.
            'speed_limit' => ['required', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
            'caption' => 'nullable|string|max:255',
            'fup_speed_limit' => ['nullable', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
            'router_ids' => 'nullable|array',
            'router_ids.*' => 'exists:routers,id',
        ]);

        $plan = Plan::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'data_cap_mb' => $request->data_cap_mb,
            'speed_limit' => $request->speed_limit,
            'caption' => $request->caption,
            'fup_speed_limit' => $request->data_cap_mb ? $request->fup_speed_limit : null,
        ]);

        // Empty selection means the plan applies to every active router.
        $plan->routers()->sync($request->input('router_ids', []));

        return redirect()->route('plans.index')->with('success', 'Plan created — syncing to hardware within a minute.');
    }

    public function edit(Plan $plan)
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();
        $selectedRouterIds = $plan->routers()->pluck('routers.id')->all();

        return view('plans.edit', compact('plan', 'routers', 'selectedRouterIds'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => ['required', Rule::in(Plan::DURATION_UNITS)],
            'data_cap_mb' => 'nullable|integer|min:1',
            'speed_limit' => ['required', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
            'caption' => 'nullable|string|max:255',
            'fup_speed_limit' => ['nullable', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
            'router_ids' => 'nullable|array',
            'router_ids.*' => 'exists:routers,id',
        ]);

        $plan->update([
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'data_cap_mb' => $request->data_cap_mb,
            'speed_limit' => $request->speed_limit,
            'caption' => $request->caption,
            'fup_speed_limit' => $request->data_cap_mb ? $request->fup_speed_limit : null,
        ]);

        $plan->routers()->sync($request->input('router_ids', []));

        return redirect()->route('plans.index')->with('success', 'Plan updated — re-syncing to hardware within a minute.');
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

    /**
     * Same in-use guard as destroy(), applied per plan — skips (rather than fails outright on)
     * any plan still assigned to a customer, and reports how many of each in one message.
     */
    public function destroyBulk(Request $request)
    {
        $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:plans,id',
        ]);

        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('id', $request->plan_ids)
            ->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($plans as $plan) {
            $inUse = PppoeUser::where('current_plan_id', $plan->id)->exists()
                || HotspotUser::where('current_plan_id', $plan->id)->exists();

            if ($inUse) {
                $skipped++;
                continue;
            }

            $plan->delete();
            $deleted++;
        }

        $message = "Removed {$deleted} plan(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} still assigned to customers.";
        }

        return redirect()->route('plans.index')->with($skipped > 0 ? 'error' : 'success', $message);
    }

    /**
     * Per-router sync status, read from what plan:reconcile last recorded (not a live query).
     */
    public function syncStatus(Plan $plan)
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->where('status', 'active')->get();

        $syncs = PlanRouterSync::where('plan_id', $plan->id)
            ->whereIn('router_id', $routers->pluck('id'))
            ->get()
            ->keyBy('router_id');

        return view('plans.sync-status', compact('plan', 'routers', 'syncs'));
    }
}

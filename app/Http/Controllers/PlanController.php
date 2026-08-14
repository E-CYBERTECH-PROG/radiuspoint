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
    // Display all plans
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

        // One aggregate query for every plan on the page, rather than a per-row query — grouped
        // so the view can tell "fully synced" from "partially synced" from "sync failed
        // somewhere" without re-deriving it per row.
        $syncCounts = PlanRouterSync::whereIn('plan_id', $plans->pluck('id'))
            ->selectRaw('plan_id, status, count(*) as c')
            ->groupBy('plan_id', 'status')
            ->get()
            ->groupBy('plan_id')
            ->map(fn ($rows) => $rows->pluck('c', 'status'));

        return view('plans.index', compact('plans', 'activeRouterCount', 'syncCounts'));
    }

    // Show the "Create Plan" form
    public function create()
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();

        return view('plans.create', compact('routers'));
    }

    // Save the plan — router sync happens separately via the plan:reconcile scheduled command
    // (see app/Console/Commands/PlanReconcile.php), not inline here.
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:hotspot,pppoe',
            'price' => 'required|numeric|min:0',
            'duration_value' => 'required|integer|min:1',
            'duration_unit' => ['required', Rule::in(Plan::DURATION_UNITS)],
            'data_cap_mb' => 'nullable|integer|min:1',
            // Mikrotik-Rate-Limit's format is rx-rate/tx-rate (e.g. "5M/5M") — a "/" is required,
            // not "." or any other separator. A malformed value here isn't a cosmetic issue: a
            // string RouterOS can't parse is silently ignored, meaning the customer gets no rate
            // limit applied at all. Confirmed live — an existing plan slipped in with "5m.5m"
            // before this validation existed and would have gone completely uncapped.
            'speed_limit' => ['required', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
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
            'fup_speed_limit' => $request->data_cap_mb ? $request->fup_speed_limit : null,
        ]);

        // Empty selection = "applies to every active router" (the pre-existing default
        // behavior) — an empty sync() call correctly leaves the pivot with no rows for that case.
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
     * Per-router sync status, read straight from what plan:reconcile last recorded — not a live
     * query, so this loads instantly regardless of how many routers this tenant has.
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

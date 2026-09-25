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
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        // "Fixed" is this app's existing term for PPPoE elsewhere (reports.fixed-sales,
        // reports.pppoe-balances) — reused here for the tab label to stay consistent.
        $tab = $request->get('tab') === 'hotspot' ? 'hotspot' : 'pppoe';
        $search = $this->searchTerm($request);

        $plans = Plan::with('routers:id')
            ->where('tenant_id', $tenantId)
            ->where('type', $tab)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($this->perPage($request, 10))
            ->withQueryString();

        $activeRouterCount = Router::where('tenant_id', $tenantId)->where('status', 'active')->count();

        // One aggregate query for sync status counts across all plans on the page.
        $syncCounts = PlanRouterSync::whereIn('plan_id', $plans->pluck('id'))
            ->selectRaw('plan_id, status, count(*) as c')
            ->groupBy('plan_id', 'status')
            ->get()
            ->groupBy('plan_id')
            ->map(fn ($rows) => $rows->pluck('c', 'status'));

        $pppoeCount = Plan::where('tenant_id', $tenantId)->where('type', 'pppoe')->count();
        $hotspotCount = Plan::where('tenant_id', $tenantId)->where('type', 'hotspot')->count();

        // For the per-row "Edit Package" modal — each plan's own current router restriction,
        // keyed for O(1) lookup in the view instead of an N+1 query per row.
        $routers = Router::where('tenant_id', $tenantId)->orderBy('name')->get();
        $planRouterIds = $plans->mapWithKeys(fn ($plan) => [$plan->id => $plan->routers->pluck('id')->all()]);

        return view('plans.index', compact('plans', 'activeRouterCount', 'syncCounts', 'tab', 'pppoeCount', 'hotspotCount', 'routers', 'planRouterIds'));
    }

    /**
     * The standalone create page was replaced by the Plans index's "Add Package" modal
     * (plans/index.blade.php) — this just sends anyone who still hits the old URL there with
     * the modal pre-opened, rather than maintaining two divergent add-plan forms.
     */
    public function create()
    {
        return redirect()->route('plans.index', ['add' => 1]);
    }

    /**
     * Clones a plan's core fields (not its router assignments — a duplicate defaults to
     * every active router, same as a brand-new plan, since carrying over the original's
     * restriction is more likely to surprise someone than help them).
     */
    public function duplicate(Plan $plan)
    {
        Plan::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => "{$plan->name} (Copy)",
            'type' => $plan->type,
            'status' => 'active',
            'price' => $plan->price,
            'duration_value' => $plan->duration_value,
            'duration_unit' => $plan->duration_unit,
            'data_cap_mb' => $plan->data_cap_mb,
            'speed_limit' => $plan->speed_limit,
            'burst_limit' => $plan->burst_limit,
            'burst_time' => $plan->burst_time,
            'caption' => $plan->caption,
            'fup_speed_limit' => $plan->fup_speed_limit,
        ]);

        return redirect()->route('plans.index', ['tab' => $plan->type])->with('success', 'Plan duplicated.');
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
            // Each side validated separately (Mikrotik-Rate-Limit format is rx-rate/tx-rate,
            // e.g. "5M/5M"; RouterOS silently ignores a value it can't parse) then joined into
            // the single speed_limit column the "Add Package" modal presents as two inputs for.
            'upload_speed' => ['required', 'string', 'regex:/^\d+[kKmM]$/'],
            'download_speed' => ['required', 'string', 'regex:/^\d+[kKmM]$/'],
            'caption' => 'nullable|string|max:255',
            'fup_speed_limit' => ['nullable', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
            'burst_upload' => ['nullable', 'required_with:burst_download', 'string', 'regex:/^\d+[kKmM]$/'],
            'burst_download' => ['nullable', 'required_with:burst_upload', 'string', 'regex:/^\d+[kKmM]$/'],
            'burst_time' => 'nullable|integer|min:1|max:60',
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
            'speed_limit' => "{$request->upload_speed}/{$request->download_speed}",
            'caption' => $request->caption,
            'fup_speed_limit' => $request->data_cap_mb ? $request->fup_speed_limit : null,
            ...$this->burstFields($request),
        ]);

        // Empty selection means the plan applies to every active router.
        $plan->routers()->sync($request->input('router_ids', []));

        return redirect()->route('plans.index', ['tab' => $plan->type])->with('success', 'Plan created — syncing to hardware within a minute.');
    }

    /**
     * The standalone edit page was replaced by the Plans index's "Edit Package" modal
     * (plans/index.blade.php) — this just sends anyone who still hits the old URL there with
     * that plan's modal pre-opened, same redirect pattern as create() above.
     */
    public function edit(Plan $plan)
    {
        return redirect()->route('plans.index', ['tab' => $plan->type, 'edit' => $plan->id]);
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
            // Same two-input Upload/Download split the "Add Package" modal uses, joined into
            // the single speed_limit column — matches store()'s validation/format exactly.
            'upload_speed' => ['required', 'string', 'regex:/^\d+[kKmM]$/'],
            'download_speed' => ['required', 'string', 'regex:/^\d+[kKmM]$/'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'caption' => 'nullable|string|max:255',
            'fup_speed_limit' => ['nullable', 'string', 'regex:/^\d+[kKmM]\/\d+[kKmM]$/'],
            'burst_upload' => ['nullable', 'required_with:burst_download', 'string', 'regex:/^\d+[kKmM]$/'],
            'burst_download' => ['nullable', 'required_with:burst_upload', 'string', 'regex:/^\d+[kKmM]$/'],
            'burst_time' => 'nullable|integer|min:1|max:60',
            'router_ids' => 'nullable|array',
            'router_ids.*' => 'exists:routers,id',
        ]);

        $plan->update([
            'name' => $request->name,
            'type' => $request->type,
            'status' => $request->status,
            'price' => $request->price,
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
            'data_cap_mb' => $request->data_cap_mb,
            'speed_limit' => "{$request->upload_speed}/{$request->download_speed}",
            'caption' => $request->caption,
            'fup_speed_limit' => $request->data_cap_mb ? $request->fup_speed_limit : null,
            ...$this->burstFields($request),
        ]);

        $plan->routers()->sync($request->input('router_ids', []));

        return redirect()->route('plans.index', ['tab' => $plan->type])->with('success', 'Plan updated — re-syncing to hardware within a minute.');
    }

    /**
     * Burst is optional: leaving both burst speeds empty clears it. When set, each side must be
     * faster than the plan's normal speed on that side — a burst below the base rate would
     * just throttle the customer for burst_time seconds, the opposite of the point.
     */
    private function burstFields(Request $request): array
    {
        if (! $request->filled('burst_upload')) {
            return ['burst_limit' => null, 'burst_time' => null];
        }

        $toKbps = fn (string $rate) => (int) $rate * (str_ends_with(strtolower($rate), 'm') ? 1000 : 1);
        $errors = [];
        foreach (['upload', 'download'] as $side) {
            if ($toKbps($request->input("burst_{$side}")) <= $toKbps($request->input("{$side}_speed"))) {
                $errors["burst_{$side}"] = 'Burst '.$side.' speed must be faster than the normal '.$side.' speed.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'burst_limit' => "{$request->burst_upload}/{$request->burst_download}",
            'burst_time' => (int) ($request->burst_time ?: 8),
        ];
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
     * Bulk Activate / Deactivate / Delete from the Packages table's checkboxes. Delete uses the
     * same in-use guard as destroy(), skipping (rather than failing on) any plan still assigned
     * to a customer. Deactivating is always safe: existing customers keep their plan.
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'integer',
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'tab' => 'nullable|in:hotspot,pppoe',
        ]);

        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('id', $request->plan_ids)
            ->get();
        $back = redirect()->route('plans.index', array_filter(['tab' => $request->tab]));

        if ($request->action !== 'delete') {
            $status = $request->action === 'activate' ? 'active' : 'inactive';
            Plan::whereIn('id', $plans->pluck('id'))->update(['status' => $status]);

            return $back->with('success', ucfirst($request->action).'d '.$plans->count().' package(s).');
        }

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

        $message = "Removed {$deleted} package(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} still assigned to customers.";
        }

        return $back->with($skipped > 0 ? 'error' : 'success', $message);
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

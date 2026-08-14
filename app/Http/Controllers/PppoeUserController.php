<?php

namespace App\Http\Controllers;

use App\Models\PppoeUser;
use App\Models\Plan;
use App\Models\Router;
use App\Services\MikrotikApiService;
use App\Services\RadiusSyncService;
use App\Services\SessionDisconnectService;
use App\Services\UsageCycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PppoeUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $users = PppoeUser::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%");
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
        $usage = $this->usageSnapshot($pppoe_user);

        return view('pppoe-users.edit', compact('pppoe_user', 'plans', 'routers', 'usage'));
    }

    /**
     * JSON snapshot backing the slide-over quick-detail panel — see
     * HotspotUserController::panel() for why this doesn't make a live router call.
     */
    public function panel(PppoeUser $pppoe_user)
    {
        $usage = $this->usageSnapshot($pppoe_user);

        return response()->json([
            'type' => 'pppoe',
            'id' => $pppoe_user->id,
            'title' => $pppoe_user->username,
            'subtitle' => $pppoe_user->name ?: $pppoe_user->plan?->name,
            'status' => $pppoe_user->status,
            'phone_number' => $pppoe_user->phone_number,
            'router_name' => $pppoe_user->router?->name,
            'expires_at' => $pppoe_user->expires_at?->format('d M Y H:i'),
            'expires_human' => $pppoe_user->expires_at?->diffForHumans(),
            'usage' => $usage ? [
                'used_mb' => $usage['used_mb'],
                'cap_mb' => $usage['cap_mb'],
                'percent' => $usage['percent'],
                'throttled' => $usage['throttled'],
                'cycle_start' => $usage['cycle_start']->format('d M Y H:i'),
            ] : null,
            'edit_url' => route('pppoe-users.edit', $pppoe_user),
            'extend_url' => route('pppoe-users.extend', $pppoe_user),
            'disconnect_url' => route('pppoe-users.disconnect', $pppoe_user),
        ]);
    }

    /**
     * "Used X of Y this cycle" — same cycle definition EnforceFairUsage checks the cap against,
     * via UsageCycleService, so what an admin sees here always matches what actually triggers a
     * throttle. Cap-less plans still show usage, just without a percentage.
     */
    private function usageSnapshot(PppoeUser $pppoe_user): ?array
    {
        if (! $pppoe_user->current_plan_id) {
            return null;
        }

        $plan = $pppoe_user->plan ?? Plan::find($pppoe_user->current_plan_id);
        if (! $plan) {
            return null;
        }

        $cycleStart = UsageCycleService::cycleStart($plan, $pppoe_user->expires_at);
        $usedBytes = UsageCycleService::bytesUsed($pppoe_user->username, $cycleStart);

        return [
            'cycle_start' => $cycleStart,
            'used_mb' => round($usedBytes / 1048576, 1),
            'cap_mb' => $plan->data_cap_mb,
            'percent' => $plan->data_cap_mb ? min(100, round($usedBytes / ($plan->data_cap_mb * 1048576) * 100)) : null,
            'throttled' => (bool) $pppoe_user->fup_throttled_at,
        ];
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

    /**
     * Either a quick "+N days" top-up from whichever is later (now or their current expiry, so
     * extending an already-active customer doesn't lose their remaining time) or a direct
     * date/time override for precise control. Reactivates an expired account in the same action
     * rather than requiring a separate status change, and clears fup_throttled_at since a new
     * expiry pushes the usage cycle forward (see UsageCycleService) — fup:enforce will naturally
     * restore full speed next run.
     */
    public function extendExpiry(Request $request, PppoeUser $pppoe_user)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'expires_at' => 'nullable|date',
        ]);

        if ($request->filled('expires_at')) {
            $newExpiry = Carbon::parse($request->expires_at);
        } elseif ($request->filled('days')) {
            $base = ($pppoe_user->expires_at && $pppoe_user->expires_at->isFuture()) ? $pppoe_user->expires_at : now();
            $newExpiry = $base->copy()->addDays((int) $request->days);
        } else {
            return $request->wantsJson()
                ? response()->json(['message' => 'Choose a number of days or a specific date.'], 422)
                : back()->with('error', 'Choose a number of days or a specific date.');
        }

        $pppoe_user->update(['status' => 'active', 'expires_at' => $newExpiry, 'fup_throttled_at' => null]);

        $plan = $pppoe_user->plan;
        if (RadiusSyncService::hasCredential($pppoe_user->username)) {
            RadiusSyncService::updateRateLimit($pppoe_user->username, $plan?->speed_limit);
        } else {
            RadiusSyncService::sync($pppoe_user->username, Str::password(10), $plan?->speed_limit);
        }
        RadiusSyncService::setExpiration($pppoe_user->username, $newExpiry);

        $message = "Extended to {$newExpiry->format('d M Y H:i')}.";

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    public function forceDisconnect(Request $request, PppoeUser $pppoe_user)
    {
        if (! $pppoe_user->router) {
            $message = 'This customer has no router on record.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $disconnected = SessionDisconnectService::disconnect(
            $pppoe_user->router, '/ppp/active/print', 'name', '/ppp/active/remove', $pppoe_user->username
        );
        $message = $disconnected ? 'Session disconnected.' : 'No active session found (they may already be offline).';

        return $request->wantsJson()
            ? response()->json(['message' => $message], $disconnected ? 200 : 422)
            : back()->with($disconnected ? 'success' : 'error', $message);
    }

    /**
     * Bulk cleanup — expired accounts that were never renewed just accumulate otherwise. No
     * "purge unused" here unlike HotspotUserController: PPPoE has no voucher/unused concept in
     * this schema (only active/expired/offline), and "offline" means a real paying customer
     * who's simply not connected right now — purging those would delete active subscribers.
     * Removes the RADIUS credential alongside each row so nothing orphaned is left in
     * radcheck/radreply.
     */
    public function purgeExpired()
    {
        $users = PppoeUser::where('tenant_id', Auth::user()->tenant_id)->where('status', 'expired')->get();

        foreach ($users as $user) {
            RadiusSyncService::remove($user->username);
        }
        PppoeUser::where('tenant_id', Auth::user()->tenant_id)->where('status', 'expired')->delete();

        return redirect()->route('pppoe-users.index')->with('success', "Purged {$users->count()} expired customer(s).");
    }

    /**
     * Polled by the index page's "Live" column. Groups the visible usernames by router and
     * does ONE /ppp/active/print call per distinct router represented on the page, rather than
     * one call per row — a page of 20 users spanning 3 routers means 3 API calls, not 20.
     */
    public function liveStatus(Request $request)
    {
        $usernames = (array) $request->input('usernames', []);

        $users = PppoeUser::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('username', $usernames)
            ->whereNotNull('current_router_id')
            ->get(['username', 'current_router_id']);

        $routers = Router::whereIn('id', $users->pluck('current_router_id')->unique())->get()->keyBy('id');
        $result = [];

        foreach ($users->groupBy('current_router_id') as $routerId => $groupUsers) {
            $router = $routers->get($routerId);
            if (! $router) {
                continue;
            }

            try {
                // Short timeout — see HotspotUserController::liveStatus() for why (same
                // sequential-per-router pattern, same confirmed PHP-FPM worker exhaustion).
                $api = new MikrotikApiService();
                if (! $api->connect($router->ip_address, $router->api_username, $router->api_password, timeout: 2)) {
                    continue;
                }

                $activeByName = collect($api->query('/ppp/active/print'))->keyBy('name');

                foreach ($groupUsers as $user) {
                    $session = $activeByName->get($user->username);
                    $result[$user->username] = $session
                        ? ['online' => true, 'uptime' => $session['uptime'] ?? null, 'address' => $session['address'] ?? null, 'id' => $session['.id'] ?? null, 'router_id' => $routerId]
                        : ['online' => false];
                }
            } catch (Throwable $e) {
                // Router unreachable — leave these usernames out of the result entirely rather
                // than reporting a false "offline"; the frontend treats a missing entry as
                // "unknown" (shows nothing) instead of a red badge.
            }
        }

        return response()->json(['data' => $result]);
    }
}

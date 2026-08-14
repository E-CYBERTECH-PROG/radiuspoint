<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
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

class HotspotUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $users = HotspotUser::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")->orWhere('mac_address', 'like', "%{$search}%");
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
            'status' => ['required', Rule::in(HotspotUser::STATUSES)],
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
        $usage = $this->usageSnapshot($hotspot_user);

        return view('hotspot-users.edit', compact('hotspot_user', 'plans', 'routers', 'usage'));
    }

    /**
     * JSON snapshot backing the slide-over quick-detail panel (clicking a username from the
     * Hotspot Users list or the dashboard) — same data the full Edit page's Quick Actions card
     * shows, just shaped for the panel instead of a server-rendered view.
     */
    /**
     * No live router call here on purpose — the index page's existing liveStatus() poller
     * already tracks online/offline for every visible row every 5s; the panel reuses that
     * already-in-memory state client-side instead of paying for a second MikroTik round trip.
     */
    public function panel(HotspotUser $hotspot_user)
    {
        $usage = $this->usageSnapshot($hotspot_user);

        return response()->json([
            'type' => 'hotspot',
            'id' => $hotspot_user->id,
            'title' => $hotspot_user->phone_number,
            'subtitle' => $hotspot_user->plan?->name,
            'status' => $hotspot_user->status,
            'mac_address' => $hotspot_user->mac_address,
            'router_name' => $hotspot_user->router?->name,
            'expires_at' => $hotspot_user->expires_at?->format('d M Y H:i'),
            'expires_human' => $hotspot_user->expires_at?->diffForHumans(),
            'usage' => $usage ? [
                'used_mb' => $usage['used_mb'],
                'cap_mb' => $usage['cap_mb'],
                'percent' => $usage['percent'],
                'throttled' => $usage['throttled'],
                'cycle_start' => $usage['cycle_start']->format('d M Y H:i'),
            ] : null,
            'edit_url' => route('hotspot-users.edit', $hotspot_user),
            'extend_url' => route('hotspot-users.extend', $hotspot_user),
            'disconnect_url' => route('hotspot-users.disconnect', $hotspot_user),
            'reset_mac_url' => route('hotspot-users.reset-mac', $hotspot_user),
        ]);
    }

    /**
     * "Used X of Y this cycle" — same cycle definition EnforceFairUsage checks the cap against,
     * via UsageCycleService, so what an admin sees here always matches what actually triggers a
     * throttle. Cap-less plans still show usage, just without a percentage.
     */
    private function usageSnapshot(HotspotUser $hotspot_user): ?array
    {
        if (! $hotspot_user->current_plan_id) {
            return null;
        }

        $plan = $hotspot_user->plan ?? Plan::find($hotspot_user->current_plan_id);
        if (! $plan) {
            return null;
        }

        $cycleStart = UsageCycleService::cycleStart($plan, $hotspot_user->expires_at);
        $usedBytes = UsageCycleService::bytesUsed($hotspot_user->phone_number, $cycleStart);

        return [
            'cycle_start' => $cycleStart,
            'used_mb' => round($usedBytes / 1048576, 1),
            'cap_mb' => $plan->data_cap_mb,
            'percent' => $plan->data_cap_mb ? min(100, round($usedBytes / ($plan->data_cap_mb * 1048576) * 100)) : null,
            'throttled' => (bool) $hotspot_user->fup_throttled_at,
        ];
    }

    public function update(Request $request, HotspotUser $hotspot_user)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'mac_address' => 'nullable|string|max:255',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => ['required', Rule::in(HotspotUser::STATUSES)],
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

    /**
     * Either a quick "+N days" top-up from whichever is later (now or their current expiry, so
     * extending an already-active customer doesn't lose their remaining time) or a direct
     * date/time override for precise control — an admin picks whichever fits. Reactivates an
     * expired account in the same action rather than requiring a separate status change, and
     * clears fup_throttled_at since a new expiry pushes the usage cycle forward (see
     * UsageCycleService) — fup:enforce will naturally restore full speed next run.
     */
    public function extendExpiry(Request $request, HotspotUser $hotspot_user)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'expires_at' => 'nullable|date',
        ]);

        if ($request->filled('expires_at')) {
            $newExpiry = Carbon::parse($request->expires_at);
        } elseif ($request->filled('days')) {
            $base = ($hotspot_user->expires_at && $hotspot_user->expires_at->isFuture()) ? $hotspot_user->expires_at : now();
            $newExpiry = $base->copy()->addDays((int) $request->days);
        } else {
            return $request->wantsJson()
                ? response()->json(['message' => 'Choose a number of days or a specific date.'], 422)
                : back()->with('error', 'Choose a number of days or a specific date.');
        }

        $hotspot_user->update(['status' => 'active', 'expires_at' => $newExpiry, 'fup_throttled_at' => null]);

        $plan = $hotspot_user->plan;
        if (RadiusSyncService::hasCredential($hotspot_user->phone_number)) {
            RadiusSyncService::updateRateLimit($hotspot_user->phone_number, $plan?->speed_limit);
        } else {
            RadiusSyncService::sync($hotspot_user->phone_number, Str::password(10), $plan?->speed_limit);
        }
        RadiusSyncService::setExpiration($hotspot_user->phone_number, $newExpiry);

        $message = "Extended to {$newExpiry->format('d M Y H:i')}.";

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Clears the bound MAC so the next device to log in with these credentials binds fresh —
     * for when a customer changes phones/routers and their old MAC lock is stopping them from
     * reconnecting.
     */
    public function resetMac(Request $request, HotspotUser $hotspot_user)
    {
        $hotspot_user->update(['mac_address' => null]);

        $message = 'MAC address cleared — the next device to connect will bind automatically.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    public function forceDisconnect(Request $request, HotspotUser $hotspot_user)
    {
        if (! $hotspot_user->router) {
            $message = 'This customer has no router on record.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $disconnected = SessionDisconnectService::disconnect(
            $hotspot_user->router, '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove', $hotspot_user->phone_number
        );
        $message = $disconnected ? 'Session disconnected.' : 'No active session found (they may already be offline).';

        return $request->wantsJson()
            ? response()->json(['message' => $message], $disconnected ? 200 : 422)
            : back()->with($disconnected ? 'success' : 'error', $message);
    }

    /**
     * Bulk cleanup, matching the "Purge Expired" / "Purge Unused" pattern — expired accounts
     * that were never renewed, and vouchers that were generated but never actually used, both
     * just accumulate otherwise. Removes the RADIUS credential alongside each row so nothing
     * orphaned is left in radcheck/radreply.
     */
    public function purgeExpired()
    {
        return $this->purge('expired');
    }

    public function purgeUnused()
    {
        return $this->purge('unused');
    }

    private function purge(string $status)
    {
        $users = HotspotUser::where('tenant_id', Auth::user()->tenant_id)->where('status', $status)->get();

        foreach ($users as $user) {
            RadiusSyncService::remove($user->phone_number);
        }
        HotspotUser::where('tenant_id', Auth::user()->tenant_id)->where('status', $status)->delete();

        return redirect()->route('hotspot-users.index')->with('success', "Purged {$users->count()} {$status} customer(s).");
    }

    /**
     * Same batched-per-router pattern as PppoeUserController::liveStatus(). Matched by
     * phone_number, not mac_address — RadiusSyncService::sync() always uses phone_number as the
     * RADIUS username, so that's what RouterOS's own active-session "user" field will contain,
     * regardless of what MAC the client connected from.
     */
    public function liveStatus(Request $request)
    {
        $phoneNumbers = (array) $request->input('phone_numbers', []);

        $users = HotspotUser::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('phone_number', $phoneNumbers)
            ->whereNotNull('current_router_id')
            ->get(['phone_number', 'current_router_id']);

        $routers = Router::whereIn('id', $users->pluck('current_router_id')->unique())->get()->keyBy('id');
        $result = [];

        foreach ($users->groupBy('current_router_id') as $routerId => $groupUsers) {
            $router = $routers->get($routerId);
            if (! $router) {
                continue;
            }

            try {
                // Short timeout (default is 5s) — this endpoint is polled every 5s and connects
                // to every distinct router in the visible list sequentially, one at a time. With
                // the default timeout, a handful of slow/unreachable routers alone could take
                // this single request past 30s, tying up a PHP-FPM worker for the whole time;
                // confirmed live in php-fpm's own log (workers SIGKILLed after 48-140s, and a
                // real "executing too slow (34.9 sec)" entry logging this exact endpoint by its
                // phone_numbers[] query string) plus cascading "Connection reset by peer" errors
                // on completely unrelated pages once the worker pool filled up. This is a
                // best-effort snapshot — a router that doesn't answer in 2s isn't likely to
                // answer meaningfully faster by waiting 5s, and a missing entry already renders
                // as "unknown" client-side rather than a hard error.
                $api = new MikrotikApiService();
                if (! $api->connect($router->ip_address, $router->api_username, $router->api_password, timeout: 2)) {
                    continue;
                }

                $activeByUser = collect($api->query('/ip/hotspot/active/print'))->keyBy('user');

                foreach ($groupUsers as $user) {
                    $session = $activeByUser->get($user->phone_number);
                    $result[$user->phone_number] = $session
                        ? ['online' => true, 'uptime' => $session['uptime'] ?? null, 'address' => $session['address'] ?? null, 'id' => $session['.id'] ?? null, 'router_id' => $routerId]
                        : ['online' => false];
                }
            } catch (Throwable $e) {
                // Router unreachable — omit these entries; frontend treats a missing key as
                // "unknown" rather than falsely reporting offline.
            }
        }

        return response()->json(['data' => $result]);
    }
}

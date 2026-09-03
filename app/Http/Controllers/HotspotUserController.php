<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use App\Services\MikrotikApiService;
use App\Services\RadiusSyncService;
use App\Services\SessionDisconnectService;
use App\Services\UsageCycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class HotspotUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        // Vouchers (is_voucher=true) have their own page (vouchers.index) — excluded here so a
        // redeemed voucher doesn't show up as a regular walk-up hotspot customer.
        $users = HotspotUser::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_voucher', false)
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

    /**
     * The standalone create page was replaced by the Customers hub's "New Customer" modal
     * (customers/index.blade.php) — this just sends anyone who still hits the old URL there
     * with the modal pre-opened, rather than maintaining two divergent add-customer forms.
     */
    public function create()
    {
        return redirect()->route('customers.index', ['type' => 'hotspot', 'add' => 1]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'mac_address' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => ['nullable', Rule::in(HotspotUser::STATUSES)],
            'expires_at' => 'nullable|date',
        ]);

        // The new "Add Customer" modal only collects first_name/last_name (no combined 'name'
        // field); the older standalone form (still POSTing here if bookmarked) sends 'name'
        // directly — accept either.
        $name = $request->input('name') ?: trim(($request->first_name ?? '').' '.($request->last_name ?? ''));
        $name = $name !== '' ? $name : null;

        // Status/expiry aren't in the new modal at all — a freshly added customer defaults to
        // active, expiring per their plan's duration from now (Plan::expiresAt()), same as
        // BillingController's immediate-activation path.
        $status = $request->input('status', 'active');
        $plan = $request->current_plan_id ? Plan::find($request->current_plan_id) : null;
        $expiresAt = $request->input('expires_at') ?: ($status === 'active' && $plan ? $plan->expiresAt() : null);

        $user = HotspotUser::create([
            'tenant_id' => Auth::user()->tenant_id,
            'phone_number' => $request->phone_number,
            'mac_address' => $request->mac_address,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $name,
            'email' => $request->email,
            'address' => $request->address,
            'current_plan_id' => $request->current_plan_id,
            'current_router_id' => $request->current_router_id,
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);

        if ($user->status === 'active') {
            RadiusSyncService::sync($user->phone_number, Str::password(10), $plan?->speed_limit);
            if ($user->expires_at) {
                RadiusSyncService::setExpiryWindow($user->phone_number, $user->expires_at);
            }
        }

        // Lets the Customers hub's "New Customer" modal (customers/index.blade.php) send the
        // user back there instead of this page's own index — only a same-app relative path is
        // ever honored, so this can't be turned into an open redirect.
        $redirectTo = $request->input('redirect_to');
        $target = ($redirectTo && str_starts_with($redirectTo, '/')) ? $redirectTo : route('hotspot-users.index');

        return redirect()->to($target)->with('success', 'Hotspot customer added successfully.');
    }

    public function edit(HotspotUser $hotspot_user)
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'hotspot')->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();
        $usage = $this->usageSnapshot($hotspot_user);

        return view('hotspot-users.edit', compact('hotspot_user', 'plans', 'routers', 'usage'));
    }

    /**
     * JSON snapshot for the quick-detail slide-over panel. No live router call here — the
     * index page's liveStatus() poller already tracks online/offline every 5s, reused client-side.
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
     * "Used X of Y this cycle" — same cycle definition EnforceFairUsage checks via
     * UsageCycleService, so this always matches what actually triggers a throttle.
     * Cap-less plans still show usage, just without a percentage.
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
        $usedBytes = UsageCycleService::bytesUsed($hotspot_user->radiusUsernames(), $cycleStart);

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
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => ['nullable', Rule::in(HotspotUser::STATUSES)],
            'expires_at' => 'nullable|date',
        ]);

        // The edit modal (customers/show.blade.php) only exposes identity/contact/package
        // fields, not status/expiry/router — those are changed via their own dedicated
        // actions (Disable/Enable, Change Expiry). Falling back to the current value keeps
        // this endpoint safe for that modal without touching the older full edit form, which
        // still sends all of them explicitly.
        $name = $request->has('first_name') || $request->has('last_name')
            ? trim(($request->first_name ?? '').' '.($request->last_name ?? '')) ?: null
            : ($request->input('name') ?: $hotspot_user->name);

        $hotspot_user->update([
            'phone_number' => $request->phone_number,
            'mac_address' => $request->input('mac_address', $hotspot_user->mac_address),
            'first_name' => $request->input('first_name', $hotspot_user->first_name),
            'last_name' => $request->input('last_name', $hotspot_user->last_name),
            'name' => $name,
            'email' => $request->input('email', $hotspot_user->email),
            'address' => $request->input('address', $hotspot_user->address),
            'current_plan_id' => $request->input('current_plan_id', $hotspot_user->current_plan_id),
            'current_router_id' => $request->input('current_router_id', $hotspot_user->current_router_id),
            'status' => $request->input('status', $hotspot_user->status),
            'expires_at' => $request->input('expires_at', $hotspot_user->expires_at),
        ]);

        if ($hotspot_user->status === 'active') {
            $plan = Plan::find($hotspot_user->current_plan_id);
            // An auto-purchased account can have two valid RADIUS credentials at once (see
            // HotspotUser::radiusUsernames()) — both need to stay in sync with plan/expiry.
            foreach ($hotspot_user->radiusUsernames() as $radiusUsername) {
                if (RadiusSyncService::hasCredential($radiusUsername)) {
                    // Preserve the existing password — only refresh the bandwidth profile.
                    RadiusSyncService::updateRateLimit($radiusUsername, $plan?->speed_limit);
                } else {
                    RadiusSyncService::sync($radiusUsername, Str::password(10), $plan?->speed_limit);
                }
                if ($hotspot_user->expires_at) {
                    RadiusSyncService::setExpiryWindow($radiusUsername, $hotspot_user->expires_at);
                }
            }
        } else {
            foreach ($hotspot_user->radiusUsernames() as $radiusUsername) {
                RadiusSyncService::remove($radiusUsername);
            }
        }

        $redirectTo = $request->input('redirect_to');
        $target = ($redirectTo && str_starts_with($redirectTo, '/')) ? $redirectTo : route('hotspot-users.index');

        return redirect()->to($target)->with('success', 'Hotspot customer updated successfully.');
    }

    public function destroy(HotspotUser $hotspot_user)
    {
        foreach ($hotspot_user->radiusUsernames() as $radiusUsername) {
            RadiusSyncService::remove($radiusUsername);
        }
        $hotspot_user->delete();

        return redirect()->route('hotspot-users.index')->with('success', 'Hotspot customer removed.');
    }

    /**
     * Wipes this customer back to a clean slate without deleting their record (unlike
     * destroy()): force-disconnects any active session, removes the RADIUS credential, clears
     * their radacct session/accounting history, and resets the bound MAC — so the next device
     * to authenticate binds fresh. Plan/expiry/billing fields are left untouched; this is
     * purely a RADIUS/session-level reset.
     */
    public function purgeCustomer(Request $request, HotspotUser $hotspot_user)
    {
        // An auto-purchased account can have two valid RADIUS credentials at once (see
        // HotspotUser::radiusUsernames()) — a real purge has to clear both, or one would
        // keep working after the "purge".
        $radiusUsernames = $hotspot_user->radiusUsernames();

        foreach ($radiusUsernames as $radiusUsername) {
            if ($hotspot_user->router) {
                SessionDisconnectService::disconnect(
                    $hotspot_user->router, '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove', $radiusUsername
                );
            }

            RadiusSyncService::remove($radiusUsername);
        }

        DB::table('radacct')->whereIn('username', $radiusUsernames)->delete();
        $hotspot_user->update(['status' => 'offline', 'mac_address' => null]);

        $message = 'Customer purged — RADIUS credential and session history cleared.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Extends expiry by +N days (from now or the current expiry, whichever is later) or sets
     * an exact date/time. Reactivates an expired account and clears fup_throttled_at so
     * fup:enforce restores full speed on its next run.
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
        foreach ($hotspot_user->radiusUsernames() as $radiusUsername) {
            if (RadiusSyncService::hasCredential($radiusUsername)) {
                RadiusSyncService::updateRateLimit($radiusUsername, $plan?->speed_limit);
            } else {
                RadiusSyncService::sync($radiusUsername, Str::password(10), $plan?->speed_limit);
            }
            RadiusSyncService::setExpiryWindow($radiusUsername, $newExpiry);
        }

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

        // An auto-purchased account can have two valid RADIUS credentials at once (see
        // HotspotUser::radiusUsernames()) — the active session could be under either.
        $disconnected = false;
        foreach ($hotspot_user->radiusUsernames() as $radiusUsername) {
            if (SessionDisconnectService::disconnect($hotspot_user->router, '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove', $radiusUsername)) {
                $disconnected = true;
            }
        }
        $message = $disconnected ? 'Session disconnected.' : 'No active session found (they may already be offline).';

        return $request->wantsJson()
            ? response()->json(['message' => $message], $disconnected ? 200 : 422)
            : back()->with($disconnected ? 'success' : 'error', $message);
    }

    /**
     * Quick "Disable" action from the customer detail page — sets offline without deleting
     * the RADIUS credential, so re-enabling later is just a status flip.
     */
    public function disable(Request $request, HotspotUser $hotspot_user)
    {
        $hotspot_user->update(['status' => 'offline']);

        $message = 'Customer disabled.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Reverses disable() — restores 'active' and makes sure a RADIUS credential actually
     * exists (disable() never removed one, but a customer created straight into 'offline'
     * would never have had one synced at all).
     */
    public function enable(Request $request, HotspotUser $hotspot_user)
    {
        $hotspot_user->update(['status' => 'active']);

        foreach ($hotspot_user->radiusUsernames() as $radiusUsername) {
            if (RadiusSyncService::hasCredential($radiusUsername)) {
                RadiusSyncService::updateRateLimit($radiusUsername, $hotspot_user->plan?->speed_limit);
            } else {
                RadiusSyncService::sync($radiusUsername, Str::password(10), $hotspot_user->plan?->speed_limit);
            }
            if ($hotspot_user->expires_at) {
                RadiusSyncService::setExpiryWindow($radiusUsername, $hotspot_user->expires_at);
            }
        }

        $message = 'Customer enabled.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Bulk-deletes expired or unused accounts and removes their RADIUS credentials so nothing
     * is left orphaned in radcheck/radreply.
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
            foreach ($user->radiusUsernames() as $radiusUsername) {
                RadiusSyncService::remove($radiusUsername);
            }
        }
        HotspotUser::where('tenant_id', Auth::user()->tenant_id)->where('status', $status)->delete();

        return redirect()->route('hotspot-users.index')->with('success', "Purged {$users->count()} {$status} customer(s).");
    }

    /**
     * Same batched-per-router pattern as PppoeUserController::liveStatus(). An auto-purchased
     * account can have two valid RADIUS credentials at once (phone_number and its M-Pesa
     * receipt — see HotspotUser::radiusUsernames()), and RouterOS's active-session "user"
     * field could be either one, so both are checked. Results stay keyed by phone_number in
     * the response since that's what the frontend requested by and renders.
     */
    public function liveStatus(Request $request)
    {
        $phoneNumbers = (array) $request->input('phone_numbers', []);

        $users = HotspotUser::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('phone_number', $phoneNumbers)
            ->whereNotNull('current_router_id')
            ->get(['id', 'phone_number', 'current_router_id']);

        // Batch-resolve radius usernames in one query instead of one-per-user.
        $receiptsByUserId = Transaction::whereIn('hotspot_user_id', $users->pluck('id'))
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->get(['hotspot_user_id', 'mpesa_receipt'])
            ->unique('hotspot_user_id')
            ->pluck('mpesa_receipt', 'hotspot_user_id');

        $routers = Router::whereIn('id', $users->pluck('current_router_id')->unique())->get()->keyBy('id');
        $result = [];

        foreach ($users->groupBy('current_router_id') as $routerId => $groupUsers) {
            $router = $routers->get($routerId);
            if (! $router) {
                continue;
            }

            try {
                // Short timeout (default 5s) — this endpoint polls every distinct router
                // sequentially, so a slow/unreachable one could tie up a PHP-FPM worker well
                // past 30s. Best-effort: a missing entry renders as "unknown" client-side.
                $api = new MikrotikApiService();
                if (! $api->connect($router->ip_address, $router->api_username, $router->api_password, timeout: 2)) {
                    continue;
                }

                $activeByUser = collect($api->query('/ip/hotspot/active/print'))->keyBy('user');

                foreach ($groupUsers as $user) {
                    $receipt = $receiptsByUserId->get($user->id);
                    $session = $activeByUser->get($user->phone_number) ?? ($receipt ? $activeByUser->get($receipt) : null);
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

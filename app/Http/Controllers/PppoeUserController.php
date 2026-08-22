<?php

namespace App\Http\Controllers;

use App\Models\PppoeUser;
use App\Models\Plan;
use App\Models\Router;
use App\Services\ExpiredBlockService;
use App\Services\MikrotikApiService;
use App\Services\RadiusSyncService;
use App\Services\SessionDisconnectService;
use App\Services\SmsTriggerService;
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

    /**
     * The standalone create page was replaced by the Customers hub's "New Customer" modal
     * (customers/index.blade.php) — this just sends anyone who still hits the old URL there
     * with the modal pre-opened, rather than maintaining two divergent add-customer forms.
     */
    public function create()
    {
        return redirect()->route('customers.index', ['tab' => 'pppoe', 'add' => 1]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => [
                'required', 'string', 'max:255',
                Rule::unique('pppoe_users')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'password' => 'nullable|string|min:4',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => 'nullable|in:active,expired,offline',
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

        $user = PppoeUser::create([
            'tenant_id' => Auth::user()->tenant_id,
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'address' => $request->address,
            'current_plan_id' => $request->current_plan_id,
            'current_router_id' => $request->current_router_id,
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);

        if ($user->status === 'active') {
            // Admin-supplied password from the modal when present, otherwise the same
            // random-password fallback the standalone form always used.
            RadiusSyncService::sync($user->username, $request->input('password') ?: Str::password(10), $plan?->speed_limit);
            if ($user->expires_at) {
                RadiusSyncService::setExpiryWindow($user->username, $user->expires_at);
            }

            SmsTriggerService::fire($user->tenant_id, 'pppoe_welcome', $user->phone_number, [
                'name' => $user->name ?: $user->username,
                'plan' => $plan?->name,
                'expires_at' => $user->expires_at?->format('d M Y H:i'),
            ]);
        }

        // Lets the Customers hub's "New Customer" modal (customers/index.blade.php) send the
        // user back there instead of this page's own index — only a same-app relative path is
        // ever honored, so this can't be turned into an open redirect.
        $redirectTo = $request->input('redirect_to');
        $target = ($redirectTo && str_starts_with($redirectTo, '/')) ? $redirectTo : route('pppoe-users.index');

        return redirect()->to($target)->with('success', 'PPPoE customer added successfully.');
    }

    public function edit(PppoeUser $pppoe_user)
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'pppoe')->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();
        $usage = $this->usageSnapshot($pppoe_user);

        return view('pppoe-users.edit', compact('pppoe_user', 'plans', 'routers', 'usage'));
    }

    /**
     * JSON snapshot backing the slide-over quick-detail panel. Does not make a live router call.
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
     * "Used X of Y this cycle", using the same cycle definition EnforceFairUsage checks against.
     * Cap-less plans still show usage, just without a percentage.
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
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'current_plan_id' => 'nullable|exists:plans,id',
            'current_router_id' => 'nullable|exists:routers,id',
            'status' => 'nullable|in:active,expired,offline',
            'expires_at' => 'nullable|date',
        ]);

        // The edit modal (customers/show.blade.php) only exposes identity/contact/package
        // fields, not status/expiry/router — those are changed via their own dedicated
        // actions (Disable/Enable, Change Expiry). Falling back to the current value keeps
        // this endpoint safe for that modal without touching the older full edit form, which
        // still sends all of them explicitly.
        $name = $request->has('first_name') || $request->has('last_name')
            ? trim(($request->first_name ?? '').' '.($request->last_name ?? '')) ?: null
            : ($request->input('name') ?: $pppoe_user->name);

        $pppoe_user->update([
            'username' => $request->username,
            'first_name' => $request->input('first_name', $pppoe_user->first_name),
            'last_name' => $request->input('last_name', $pppoe_user->last_name),
            'name' => $name,
            'email' => $request->input('email', $pppoe_user->email),
            'address' => $request->input('address', $pppoe_user->address),
            'phone_number' => $request->input('phone_number', $pppoe_user->phone_number),
            'current_plan_id' => $request->input('current_plan_id', $pppoe_user->current_plan_id),
            'current_router_id' => $request->input('current_router_id', $pppoe_user->current_router_id),
            'status' => $request->input('status', $pppoe_user->status),
            'expires_at' => $request->input('expires_at', $pppoe_user->expires_at),
        ]);

        if ($pppoe_user->status === 'active') {
            $plan = Plan::find($pppoe_user->current_plan_id);
            if (RadiusSyncService::hasCredential($pppoe_user->username)) {
                RadiusSyncService::updateRateLimit($pppoe_user->username, $plan?->speed_limit);
            } else {
                RadiusSyncService::sync($pppoe_user->username, Str::password(10), $plan?->speed_limit);
            }
            if ($pppoe_user->expires_at) {
                RadiusSyncService::setExpiryWindow($pppoe_user->username, $pppoe_user->expires_at);
            }
        } else {
            RadiusSyncService::remove($pppoe_user->username);
        }

        // Same same-app-only redirect guard as store() — lets the customer detail page's edit
        // modal send the admin back there instead of this page's own index.
        $redirectTo = $request->input('redirect_to');
        $target = ($redirectTo && str_starts_with($redirectTo, '/')) ? $redirectTo : route('pppoe-users.index');

        return redirect()->to($target)->with('success', 'PPPoE customer updated successfully.');
    }

    public function destroy(PppoeUser $pppoe_user)
    {
        RadiusSyncService::remove($pppoe_user->username);
        $pppoe_user->delete();

        return redirect()->route('pppoe-users.index')->with('success', 'PPPoE customer removed.');
    }

    /**
     * Extends expiry by "+N days" from now or the current expiry (whichever is later), or sets
     * an exact date/time. Reactivates an expired account and clears fup_throttled_at so
     * fup:enforce restores full speed on its next run.
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

        $pppoe_user->update([
            'status' => 'active',
            'expires_at' => $newExpiry,
            'fup_throttled_at' => null,
            'expiry_reminder_sent_at' => null,
        ]);

        $plan = $pppoe_user->plan;
        if (RadiusSyncService::hasCredential($pppoe_user->username)) {
            RadiusSyncService::updateRateLimit($pppoe_user->username, $plan?->speed_limit);
        } else {
            RadiusSyncService::sync($pppoe_user->username, Str::password(10), $plan?->speed_limit);
        }
        RadiusSyncService::setExpiryWindow($pppoe_user->username, $newExpiry);
        ExpiredBlockService::clear($pppoe_user->router, $pppoe_user->username);

        SmsTriggerService::fire($pppoe_user->tenant_id, 'pppoe_renewed', $pppoe_user->phone_number, [
            'name' => $pppoe_user->name ?: $pppoe_user->username,
            'plan' => $plan?->name,
            'expires_at' => $newExpiry->format('d M Y H:i'),
        ]);

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
     * Quick "Disable" action from the customer detail page — sets offline without deleting
     * the RADIUS credential (unlike purgeExpired), so re-enabling later is just a status flip.
     */
    public function disable(Request $request, PppoeUser $pppoe_user)
    {
        $pppoe_user->update(['status' => 'offline']);

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
    public function enable(Request $request, PppoeUser $pppoe_user)
    {
        $pppoe_user->update(['status' => 'active']);

        if (RadiusSyncService::hasCredential($pppoe_user->username)) {
            RadiusSyncService::updateRateLimit($pppoe_user->username, $pppoe_user->plan?->speed_limit);
        } else {
            RadiusSyncService::sync($pppoe_user->username, Str::password(10), $pppoe_user->plan?->speed_limit);
        }
        if ($pppoe_user->expires_at) {
            RadiusSyncService::setExpiryWindow($pppoe_user->username, $pppoe_user->expires_at);
        }

        $message = 'Customer enabled.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Re-syncs the RADIUS password only — everything else about the credential (rate limit,
     * expiration) is left as RadiusSyncService already has it.
     */
    public function changePassword(Request $request, PppoeUser $pppoe_user)
    {
        $request->validate(['password' => 'required|string|min:4']);

        RadiusSyncService::sync($pppoe_user->username, $request->password, $pppoe_user->plan?->speed_limit);

        $message = 'Password updated.';

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Bulk-deletes expired accounts and their RADIUS credentials. Only "expired" status is
     * removed — "offline" means a paying customer who's simply not connected right now.
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
     * Polled by the index page's "Live" column. Makes one /ppp/active/print call per distinct
     * router on the page instead of one call per user row.
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
                // Short timeout to avoid exhausting PHP-FPM workers on unreachable routers.
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
                // Router unreachable — omit these usernames rather than reporting false "offline".
            }
        }

        return response()->json(['data' => $result]);
    }
}

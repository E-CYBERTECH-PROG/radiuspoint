<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Transaction;
use App\Services\UsageCycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PPPoE and Hotspot customers each get their own URL (/customers/pppoe, /customers/hotspot —
 * see the sidebar's CRM > Customers submenu) but share this controller and the same
 * customers.index/customers.show Blade views, since the two tables/pages are otherwise
 * identical in layout. "Static" and "DHCP" tabs are shown inert — this app has no such
 * connection type.
 */
class CustomerController extends Controller
{
    public function index(Request $request, string $type)
    {
        $tenantId = Auth::user()->tenant_id;
        $tab = $type;
        $search = $this->searchTerm($request);

        $plans = Plan::where('tenant_id', $tenantId)->where('type', $tab)->get();
        $routers = Router::where('tenant_id', $tenantId)->get()->keyBy('id');

        // Registered walk-up customers and vouchers used to live on entirely separate pages
        // (Customers > Hotspot vs. a standalone Vouchers page) — merged here behind this
        // filter so an admin isn't hunting across two places for the same underlying
        // HotspotUser record. Defaults to showing both together. Meaningless for the PPPoE
        // tab (no such thing as a PPPoE voucher), but still defined so the view can
        // unconditionally check it.
        $kind = $tab === 'hotspot' && in_array($request->get('kind'), ['all', 'registered', 'voucher'], true)
            ? $request->get('kind')
            : 'all';

        if ($tab === 'hotspot') {
            $users = HotspotUser::where('tenant_id', $tenantId)
                ->when($kind === 'registered', fn ($q) => $q->where('is_voucher', false))
                ->when($kind === 'voucher', fn ($q) => $q->where('is_voucher', true))
                ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                    $q->where('phone_number', 'like', "%{$search}%")->orWhere('mac_address', 'like', "%{$search}%");
                }))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('plan_id'), fn ($q) => $q->where('current_plan_id', $request->plan_id))
                ->when($request->filled('router_id'), fn ($q) => $q->where('current_router_id', $request->router_id))
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString();
        } else {
            $users = PppoeUser::where('tenant_id', $tenantId)
                ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                }))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('plan_id'), fn ($q) => $q->where('current_plan_id', $request->plan_id))
                ->when($request->filled('router_id'), fn ($q) => $q->where('current_router_id', $request->router_id))
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString();
        }

        $allPlans = Plan::where('tenant_id', $tenantId)->get()->keyBy('id');

        // Split out for the "Add Customer" modal, which lets the connection type be switched
        // client-side independently of this page's own $tab — it needs both lists on hand to
        // swap the Package dropdown's options without a round-trip.
        $pppoePlans = $allPlans->where('type', 'pppoe')->values();
        $hotspotPlans = $allPlans->where('type', 'hotspot')->values();

        // Scoped to this page's own type only — a PPPoE customer shouldn't inflate the KPI
        // tiles on the Hotspot page or vice versa. Also scoped to the current kind filter
        // (registered/voucher/all) so the tiles always describe exactly what's in the table
        // below them, matching the dashboard's own decision to count vouchers as real
        // customers rather than a separate category.
        if ($tab === 'hotspot') {
            $statsQuery = fn () => HotspotUser::where('tenant_id', $tenantId)
                ->when($kind === 'registered', fn ($q) => $q->where('is_voucher', false))
                ->when($kind === 'voucher', fn ($q) => $q->where('is_voucher', true));

            $stats = [
                'total' => $statsQuery()->count(),
                'active' => $statsQuery()->where('status', 'active')->count(),
                'expired' => $statsQuery()->where('status', 'expired')->count(),
                'disabled' => $statsQuery()->whereIn('status', ['offline', 'unused'])->count(),
            ];
        } else {
            $stats = [
                'total' => PppoeUser::where('tenant_id', $tenantId)->count(),
                'active' => PppoeUser::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'expired' => PppoeUser::where('tenant_id', $tenantId)->where('status', 'expired')->count(),
                'disabled' => PppoeUser::where('tenant_id', $tenantId)->where('status', 'offline')->count(),
            ];
        }

        return view('customers.index', compact(
            'users', 'plans', 'allPlans', 'pppoePlans', 'hotspotPlans', 'routers', 'tab', 'stats', 'kind'
        ));
    }

    /**
     * Full customer profile page — replaces the old slide-over panel. Renders everything
     * server-side (no JSON fetch-on-load like the panel had) since it now shows much more
     * than the panel ever did: transaction history and the latest RADIUS session, on top of
     * the same status/plan/usage/expiry the panel already covered.
     */
    public function show(string $type, string $token)
    {
        [$decodedType, $id] = self::decodeToken($token);

        // The route's {type} segment (customers/pppoe/view/... vs customers/hotspot/view/...)
        // must agree with what the token itself encodes — guards against a stale/copy-pasted
        // link from the other type resolving to the wrong model.
        if ($decodedType !== $type) {
            abort(404);
        }

        $tenantId = Auth::user()->tenant_id;

        $user = $type === 'hotspot'
            ? HotspotUser::where('tenant_id', $tenantId)->with(['plan', 'router'])->findOrFail($id)
            : PppoeUser::where('tenant_id', $tenantId)->with(['plan', 'router'])->findOrFail($id);

        $usage = $this->usageSnapshot($user);

        // hotspot_user_id is a real FK (set at M-Pesa checkout); pppoe_users has no equivalent
        // column, so phone_number is the only correlation available for that side.
        $transactions = Transaction::where('tenant_id', $tenantId)
            ->where(function ($q) use ($user, $type) {
                if ($type === 'hotspot') {
                    $q->where('hotspot_user_id', $user->id);
                }
                if ($user->phone_number) {
                    $q->orWhere('phone_number', $user->phone_number);
                }
            })
            ->latest()
            ->limit(50)
            ->get();

        $totalSpent = $transactions->where('status', 'success')->sum('amount');

        // radacct doesn't distinguish PPPoE from Hotspot sessions — matched by whichever
        // field is this customer's actual RADIUS username (see BelongsToTenant note in
        // DashboardController's git history for the same distinction). Each accounting row
        // existing at all implies the auth that started it was accepted (radacct only gets a
        // row once a session actually starts), so these double as the "Access-Accept" log the
        // detail page's connection-log timeline shows — this app doesn't have a radpostauth
        // table logging raw auth accept/reject events.
        // An auto-purchased hotspot account can have two valid RADIUS usernames at once (see
        // HotspotUser::radiusUsernames()) — sessions under either belong to this customer.
        $radiusUsernames = $type === 'hotspot' ? $user->radiusUsernames() : [$user->username];
        $connectionLogs = DB::table('radacct')->whereIn('username', array_filter($radiusUsernames))->orderByDesc('acctstarttime')->limit(10)->get();

        // Device Session Data card (hotspot only) — built from the latest radacct row already
        // fetched above rather than a live MikroTik call, so the page never waits on the
        // router: a session with no acctstoptime yet is still open (currently online), and its
        // nasipaddress resolves back to which of this tenant's routers ("site") it's on.
        $liveSession = null;
        if ($type === 'hotspot' && ($latest = $connectionLogs->first())) {
            $liveSession = [
                'online' => is_null($latest->acctstoptime),
                'ip_address' => $latest->framedipaddress,
                'mac_address' => $latest->callingstationid,
                'site' => Router::where('tenant_id', $tenantId)->where('ip_address', $latest->nasipaddress)->value('name'),
                // FreeRADIUS writes acctstarttime in UTC regardless of the app/tenant timezone
                // (see RadiusSyncService::firstSessionStart()) — parsing it without an explicit
                // source timezone reads it as app-local (Africa/Nairobi, UTC+3), silently
                // shifting "online since"/uptime 3 hours into the past.
                'started_at' => Carbon::parse($latest->acctstarttime, 'UTC')->setTimezone(config('app.timezone')),
            ];
        }

        $plans = Plan::where('tenant_id', $tenantId)->where('type', $type)->get();

        return view('customers.show', [
            'type' => $type,
            'user' => $user,
            'usage' => $usage,
            'transactions' => $transactions,
            'totalSpent' => $totalSpent,
            'connectionLogs' => $connectionLogs,
            'liveSession' => $liveSession,
            'token' => $token,
            'plans' => $plans,
        ]);
    }

    /**
     * Same "used X of Y this cycle" calculation as PppoeUserController/HotspotUserController's
     * private usageSnapshot() — duplicated rather than shared across three controllers for
     * two structurally-unrelated models; all three call the same UsageCycleService methods,
     * so they can't drift on the actual cycle/throttle logic.
     */
    private function usageSnapshot(PppoeUser|HotspotUser $user): ?array
    {
        if (! $user->current_plan_id || ! $user->plan) {
            return null;
        }

        $usernames = $user instanceof HotspotUser ? $user->radiusUsernames() : [$user->username];
        $cycleStart = UsageCycleService::cycleStart($user->plan, $user->expires_at);
        $usedBytes = UsageCycleService::bytesUsed($usernames, $cycleStart);

        return [
            'cycle_start' => $cycleStart,
            'used_mb' => round($usedBytes / 1048576, 1),
            'cap_mb' => $user->plan->data_cap_mb,
            'percent' => $user->plan->data_cap_mb ? min(100, round($usedBytes / ($user->plan->data_cap_mb * 1048576) * 100)) : null,
        ];
    }

    /**
     * Opaque-looking but reversible "type:id" token for /customers/view/{token} — avoids a
     * schema change (a real UUID column) purely to keep sequential ids out of an
     * authenticated, tenant-scoped admin URL that was never a leak risk to begin with.
     */
    public static function tokenFor(string $type, int $id): string
    {
        return rtrim(strtr(base64_encode("{$type}:{$id}"), '+/', '-_'), '=');
    }

    private static function decodeToken(string $token): array
    {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        [$type, $id] = array_pad(explode(':', (string) $decoded, 2), 2, null);

        if (! in_array($type, ['pppoe', 'hotspot'], true) || ! ctype_digit((string) $id)) {
            abort(404);
        }

        return [$type, (int) $id];
    }
}

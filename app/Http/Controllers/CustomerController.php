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
 * Single "hub" listing that sits behind the sidebar's Customers link — PPPoE and Hotspot
 * customers are still separate tables/pages under the hood (pppoe-users.*, hotspot-users.*),
 * this just tabs between them from one screen instead of making that split a sidebar
 * dropdown. "Static" and "DHCP" tabs are shown inert — this app has no such connection type.
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $tab = $request->get('tab') === 'hotspot' ? 'hotspot' : 'pppoe';
        $search = $this->searchTerm($request);

        $plans = Plan::where('tenant_id', $tenantId)->where('type', $tab)->get();
        $routers = Router::where('tenant_id', $tenantId)->get()->keyBy('id');

        if ($tab === 'hotspot') {
            $users = HotspotUser::where('tenant_id', $tenantId)
                ->where('is_voucher', false)
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

        $pppoeCount = PppoeUser::where('tenant_id', $tenantId)->count();
        // Vouchers are hotspot_users rows under the hood (is_voucher=true) but aren't real
        // walk-up customers until redeemed, and even then belong on their own Vouchers page
        // (vouchers.index) — excluded from every count/list here so they don't inflate or
        // pollute the Customers hub.
        $hotspotCount = HotspotUser::where('tenant_id', $tenantId)->where('is_voucher', false)->count();

        $stats = [
            'total' => $pppoeCount + $hotspotCount,
            'active' => PppoeUser::where('tenant_id', $tenantId)->where('status', 'active')->count()
                + HotspotUser::where('tenant_id', $tenantId)->where('is_voucher', false)->where('status', 'active')->count(),
            'expired' => PppoeUser::where('tenant_id', $tenantId)->where('status', 'expired')->count()
                + HotspotUser::where('tenant_id', $tenantId)->where('is_voucher', false)->where('status', 'expired')->count(),
            'disabled' => PppoeUser::where('tenant_id', $tenantId)->where('status', 'offline')->count()
                + HotspotUser::where('tenant_id', $tenantId)->where('is_voucher', false)->whereIn('status', ['offline', 'unused'])->count(),
        ];

        return view('customers.index', compact(
            'users', 'plans', 'allPlans', 'pppoePlans', 'hotspotPlans', 'routers', 'tab',
            'stats', 'pppoeCount', 'hotspotCount'
        ));
    }

    /**
     * Full customer profile page — replaces the old slide-over panel. Renders everything
     * server-side (no JSON fetch-on-load like the panel had) since it now shows much more
     * than the panel ever did: transaction history and the latest RADIUS session, on top of
     * the same status/plan/usage/expiry the panel already covered.
     */
    public function show(string $token)
    {
        [$type, $id] = self::decodeToken($token);

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
        $radiusUsername = $type === 'hotspot' ? $user->radiusUsername() : $user->username;
        $connectionLogs = $radiusUsername
            ? DB::table('radacct')->where('username', $radiusUsername)->orderByDesc('acctstarttime')->limit(10)->get()
            : collect();

        $plans = Plan::where('tenant_id', $tenantId)->where('type', $type)->get();

        return view('customers.show', [
            'type' => $type,
            'user' => $user,
            'usage' => $usage,
            'transactions' => $transactions,
            'totalSpent' => $totalSpent,
            'connectionLogs' => $connectionLogs,
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

        $username = $user instanceof HotspotUser ? $user->radiusUsername() : $user->username;
        $cycleStart = UsageCycleService::cycleStart($user->plan, $user->expires_at);
        $usedBytes = UsageCycleService::bytesUsed($username, $cycleStart);

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

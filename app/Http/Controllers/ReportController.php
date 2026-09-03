<?php

namespace App\Http\Controllers;

use App\Models\CaptivePortalVisit;
use App\Models\PppoeUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Union of expired Hotspot + PPPoE accounts, built as a union subquery so filters and
     * pagination apply to the combined set.
     */
    public function expiredUsers(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $this->searchTerm($request);

        $hotspot = DB::table('hotspot_users')
            ->where('tenant_id', $tenantId)
            ->where('status', 'expired')
            ->where('is_voucher', false)
            ->selectRaw("'hotspot' as type, id, phone_number as identifier, NULL as name, current_plan_id, current_router_id, expires_at");

        $pppoe = DB::table('pppoe_users')
            ->where('tenant_id', $tenantId)
            ->where('status', 'expired')
            ->selectRaw("'pppoe' as type, id, username as identifier, name, current_plan_id, current_router_id, expires_at");

        $union = $hotspot->unionAll($pppoe);

        $users = DB::query()->fromSub($union, 'expired_users')
            ->when($search, fn ($q) => $q->where('identifier', 'like', "%{$search}%"))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expires_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expires_at', '<=', $request->to))
            ->orderByDesc('expires_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $plans = Plan::where('tenant_id', $tenantId)->get()->keyBy('id');
        $routers = Router::where('tenant_id', $tenantId)->get()->keyBy('id');

        return view('reports.expired-users', compact('users', 'plans', 'routers'));
    }

    /**
     * Transaction receipts — search by M-Pesa receipt code or date range.
     */
    public function receipts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $code = trim((string) $request->query('receipt', ''));

        $transactions = Transaction::where('tenant_id', $tenantId)
            ->where('status', 'success')
            ->when($code !== '', fn ($q) => $q->where('mpesa_receipt', 'like', "%{$code}%"))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('reports.receipts', compact('transactions'));
    }

    /**
     * Manually record a payment that didn't come through M-Pesa STK (cash, bank transfer,
     * etc.) — created straight to status=success since it's already been received by the
     * time staff are logging it here.
     */
    public function recordPayment(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'package_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'mpesa_receipt' => 'nullable|string|max:255|unique:transactions,mpesa_receipt',
        ]);

        Transaction::create([
            'tenant_id' => Auth::user()->tenant_id,
            'customer_name' => $validated['customer_name'],
            'phone_number' => $validated['phone_number'],
            'package_name' => $validated['package_name'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'mpesa_receipt' => $validated['mpesa_receipt'] ?? null,
            'status' => 'success',
        ]);

        return redirect()->route('reports.receipts')->with('success', 'Payment recorded.');
    }

    /**
     * Standalone printable receipt — bare <html>, no sidebar chrome.
     */
    public function receiptPrint(Transaction $transaction)
    {
        abort_unless($transaction->tenant_id === Auth::user()->tenant_id, 404);
        abort_unless($transaction->status === 'success', 404);

        return view('reports.receipt-print', compact('transaction'));
    }

    public function pppoeBalances(Request $request)
    {
        $search = $this->searchTerm($request);

        $users = PppoeUser::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where('username', 'like', "%{$search}%"))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->get()->keyBy('id');

        return view('reports.pppoe-balances', compact('users', 'plans'));
    }

    public function fixedSales(Request $request)
    {
        return $this->salesReport('pppoe', 'reports.fixed-sales', $request);
    }

    public function hotspotSales(Request $request)
    {
        return $this->salesReport('hotspot', 'reports.hotspot-sales', $request);
    }

    protected function salesReport(string $type, string $view, Request $request)
    {
        $planNames = Plan::where('tenant_id', Auth::user()->tenant_id)
            ->where('type', $type)
            ->pluck('name');

        $query = Transaction::where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'success')
            ->whereIn('package_name', $planNames)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));

        // Totals must reflect the whole filtered set, not just the current page.
        $totalSales = (clone $query)->count();
        $totalRevenue = (clone $query)->sum('amount');

        $transactions = $query->latest()->paginate($this->perPage($request))->withQueryString();

        return view($view, compact('transactions', 'totalSales', 'totalRevenue'));
    }

    public function accessLog(Request $request)
    {
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();
        $routersByIp = $routers->keyBy('ip_address');
        $search = $this->searchTerm($request);

        $logs = DB::table('radacct')
            ->whereIn('nasipaddress', $routers->pluck('ip_address'))
            ->when($search, fn ($q) => $q->where('username', 'like', "%{$search}%"))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('acctstarttime', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('acctstarttime', '<=', $request->to))
            ->orderByDesc('acctstarttime')
            ->paginate($this->perPage($request, 50))
            ->withQueryString();

        return view('reports.access-log', compact('logs', 'routersByIp'));
    }

    /**
     * Charts for revenue and data throughput per router, top data-consuming customers, and
     * best-selling packages. Computed live from Transaction/radacct.
     */
    public function analytics()
    {
        $tenantId = Auth::user()->tenant_id;
        $routers = Router::where('tenant_id', $tenantId)->get();

        // Hotspot-only revenue (Transaction has no pppoe_user_id; PPPoE isn't sold via M-Pesa).
        // withoutGlobalScope + explicit transactions.tenant_id filter: the BelongsToTenant
        // global scope uses an unqualified tenant_id column, which is ambiguous once this query
        // joins other tenant-scoped tables (hotspot_users, routers) with their own tenant_id.
        $revenueByRouter = Transaction::withoutGlobalScope('tenant')
            ->where('transactions.tenant_id', $tenantId)
            ->where('transactions.status', 'success')
            ->whereNotNull('transactions.hotspot_user_id')
            ->join('hotspot_users', 'transactions.hotspot_user_id', '=', 'hotspot_users.id')
            ->join('routers', 'hotspot_users.current_router_id', '=', 'routers.id')
            ->selectRaw('routers.name as router_name, SUM(transactions.amount) as total, COUNT(*) as sales')
            ->groupBy('routers.id', 'routers.name')
            ->orderByDesc('total')
            ->get();

        // Accumulated data throughput + unique customer count per router, from radacct.
        $usageByRouter = DB::table('radacct')
            ->whereIn('nasipaddress', $routers->pluck('ip_address'))
            ->selectRaw('nasipaddress, SUM(acctinputoctets + acctoutputoctets) as total_bytes, COUNT(DISTINCT username) as unique_users')
            ->groupBy('nasipaddress')
            ->get()
            ->map(function ($row) use ($routers) {
                $router = $routers->firstWhere('ip_address', $row->nasipaddress);

                return [
                    'router_name' => $router?->name ?? $row->nasipaddress,
                    'total_gb' => round($row->total_bytes / 1073741824, 2),
                    'unique_users' => $row->unique_users,
                ];
            })
            ->sortByDesc('total_gb')
            ->values();

        // Top 10 data-consuming customers across hotspot and PPPoE. Excludes vouchers — not
        // real walk-up customers. A hotspot session's radacct username is phone_number only
        // for vouchers/manually created accounts; an auto-purchased account's actual RADIUS
        // credential is its Transaction's M-Pesa receipt (see HotspotUser::radiusUsername()),
        // so both are indexed here.
        $hotspotByPhone = \App\Models\HotspotUser::where('tenant_id', $tenantId)->where('is_voucher', false)->pluck('phone_number')->flip();
        $hotspotByReceipt = Transaction::where('tenant_id', $tenantId)
            ->where('status', 'success')
            ->whereNotNull('hotspot_user_id')
            ->whereNotNull('mpesa_receipt')
            ->pluck('mpesa_receipt')
            ->flip();
        $pppoeByUsername = \App\Models\PppoeUser::where('tenant_id', $tenantId)->pluck('username')->flip();

        $topUsers = DB::table('radacct')
            ->whereIn('nasipaddress', $routers->pluck('ip_address'))
            ->selectRaw('username, SUM(acctinputoctets + acctoutputoctets) as total_bytes')
            ->groupBy('username')
            ->orderByDesc('total_bytes')
            ->limit(10)
            ->get()
            ->filter(fn ($row) => isset($hotspotByPhone[$row->username]) || isset($hotspotByReceipt[$row->username]) || isset($pppoeByUsername[$row->username]))
            ->map(fn ($row) => [
                'username' => $row->username,
                'type' => (isset($hotspotByPhone[$row->username]) || isset($hotspotByReceipt[$row->username])) ? 'Hotspot' : 'PPPoE',
                'total_gb' => round($row->total_bytes / 1073741824, 2),
            ])
            ->values();

        // Most-purchased packages, any type.
        $topPackages = Transaction::where('tenant_id', $tenantId)
            ->where('status', 'success')
            ->selectRaw('package_name, COUNT(*) as purchase_count, SUM(amount) as total_revenue')
            ->groupBy('package_name')
            ->orderByDesc('purchase_count')
            ->limit(10)
            ->get();

        // Portal funnel, last 30 days: visits vs. paid conversions vs. Free Mode fallbacks.
        // Free Mode sessions are counted via radcheck's created_at (username pattern free_<random>).
        $since = now()->subDays(30);
        $portalVisits = CaptivePortalVisit::where('tenant_id', $tenantId)->where('created_at', '>=', $since)->count();
        $portalConversions = Transaction::where('tenant_id', $tenantId)->where('status', 'success')->where('created_at', '>=', $since)->count();
        $freeModeSessions = DB::table('radcheck')
            ->where('username', 'like', 'free\_%')
            ->where('created_at', '>=', $since)
            ->distinct('username')
            ->count('username');
        $conversionRate = $portalVisits > 0 ? round($portalConversions / $portalVisits * 100, 1) : null;

        return view('reports.analytics', compact(
            'revenueByRouter', 'usageByRouter', 'topUsers', 'topPackages',
            'portalVisits', 'portalConversions', 'freeModeSessions', 'conversionRate'
        ));
    }
}

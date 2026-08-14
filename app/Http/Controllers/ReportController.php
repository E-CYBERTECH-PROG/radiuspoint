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
     * Four data-driven charts answering "where's the money/usage actually coming from": revenue
     * and accumulated data-throughput per router (so a tenant can see which physical site is
     * actually earning/carrying the load), top data-consuming customers, and which packages
     * actually sell. All computed live from Transaction/radacct — nothing fabricated.
     */
    public function analytics()
    {
        $tenantId = Auth::user()->tenant_id;
        $routers = Router::where('tenant_id', $tenantId)->get();

        // Revenue by router — only hotspot transactions carry a router link today (Transaction
        // only has hotspot_user_id, no pppoe_user_id; PPPoE customers are managed manually per
        // this project's schema, not sold self-service via M-Pesa), so this is explicitly
        // hotspot-only revenue, not total revenue.
        // withoutGlobalScope('tenant') + an explicit transactions.tenant_id filter: the
        // BelongsToTenant trait's global scope writes an unqualified `tenant_id` column, which
        // becomes ambiguous the moment this query joins two other tenant-scoped tables
        // (hotspot_users, routers) that also have their own tenant_id column — confirmed via a
        // real SQLSTATE 23000 "Column 'tenant_id' in WHERE is ambiguous" on first render.
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

        // Accumulated data throughput + unique customer count per router, straight from radacct
        // (same nasipaddress-scoping pattern as accessLog()/DashboardController::liveOnlineCount()).
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

        // Top 10 data-consuming customers across both hotspot and PPPoE, labeled back to
        // whichever side actually owns that username.
        $hotspotByPhone = \App\Models\HotspotUser::where('tenant_id', $tenantId)->pluck('phone_number')->flip();
        $pppoeByUsername = \App\Models\PppoeUser::where('tenant_id', $tenantId)->pluck('username')->flip();

        $topUsers = DB::table('radacct')
            ->whereIn('nasipaddress', $routers->pluck('ip_address'))
            ->selectRaw('username, SUM(acctinputoctets + acctoutputoctets) as total_bytes')
            ->groupBy('username')
            ->orderByDesc('total_bytes')
            ->limit(10)
            ->get()
            ->filter(fn ($row) => isset($hotspotByPhone[$row->username]) || isset($pppoeByUsername[$row->username]))
            ->map(fn ($row) => [
                'username' => $row->username,
                'type' => isset($hotspotByPhone[$row->username]) ? 'Hotspot' : 'PPPoE',
                'total_gb' => round($row->total_bytes / 1073741824, 2),
            ])
            ->values();

        // Most-purchased packages, any type — count and revenue.
        $topPackages = Transaction::where('tenant_id', $tenantId)
            ->where('status', 'success')
            ->selectRaw('package_name, COUNT(*) as purchase_count, SUM(amount) as total_revenue')
            ->groupBy('package_name')
            ->orderByDesc('purchase_count')
            ->limit(10)
            ->get();

        // Portal funnel, last 30 days: visits (every real page load, logged in show()) vs. how
        // many actually converted to a paid purchase or fell back to Free Mode — previously the
        // only visibility into portal performance was after the fact (successful Transactions
        // alone), with no way to tell "50 bought out of 50 who ever saw the page" from "out of
        // 5,000". Free Mode sessions counted via radcheck's own created_at (their username is
        // always the free_<random> pattern CaptivePortalController::freeMode() generates).
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

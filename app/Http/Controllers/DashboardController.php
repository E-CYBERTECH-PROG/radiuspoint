<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // radacct doesn't distinguish PPPoE from Hotspot sessions itself — matched by whether
        // the open session's username belongs to a PppoeUser or a HotspotUser (phone_number is
        // the RADIUS username for hotspot customers). Scoped to this tenant's own routers since
        // radacct has no tenant_id column.
        $routerIps = Router::where('tenant_id', Auth::user()->tenant_id)->pluck('ip_address');
        $openSessionUsernames = $routerIps->isEmpty()
            ? collect()
            : DB::table('radacct')->whereIn('nasipaddress', $routerIps)->whereNull('acctstoptime')->pluck('username');

        $onlinePpp = $openSessionUsernames->isEmpty() ? 0 : PppoeUser::whereIn('username', $openSessionUsernames)->count();
        $onlineHotspot = $openSessionUsernames->isEmpty() ? 0 : HotspotUser::whereIn('phone_number', $openSessionUsernames)->count();

        $stats = [
            'income_today' => (float) Transaction::whereDate('created_at', $now->copy()->startOfDay())->where('status', 'success')->sum('amount'),
            'income_month' => (float) Transaction::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->where('status', 'success')->sum('amount'),
            'income_last_month' => (float) Transaction::whereMonth('created_at', $now->copy()->subMonth()->month)->whereYear('created_at', $now->copy()->subMonth()->year)->where('status', 'success')->sum('amount'),
            'hotspot_active' => HotspotUser::where('status', 'active')->count(),
            'pppoe_active' => PppoeUser::where('status', 'active')->count(),
            'online_now' => $onlinePpp + $onlineHotspot,
            'customers_total' => HotspotUser::count() + PppoeUser::count(),
            'customers_expired' => HotspotUser::where('status', 'expired')->count() + PppoeUser::where('status', 'expired')->count(),
            'online_ppp' => $onlinePpp,
            'online_hotspot' => $onlineHotspot,
        ];

        $chartLabels = [];
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $chartLabels[] = $month->format('M');
            $chartData[] = (float) Transaction::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->where('status', 'success')
                ->sum('amount');
        }

        // Same 6-month window as the revenue chart above, so the two charts line up. "Growth"
        // here is new hotspot+PPPoE customers created that month, combined.
        $growthLabels = $chartLabels;
        $growthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $growthData[] = HotspotUser::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count()
                + PppoeUser::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
        }

        $subscriptionsThisMonth = HotspotUser::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count()
            + PppoeUser::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();

        // Last 6 days — a short, glanceable sparkline next to "this month"'s headline number,
        // deliberately finer-grained than the 6-month growth chart above.
        $subscriptionsSparkline = [];
        for ($i = 5; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $subscriptionsSparkline[] = HotspotUser::whereDate('created_at', $day)->count()
                + PppoeUser::whereDate('created_at', $day)->count();
        }

        // Top packages by revenue this month, matched via Transaction.plan_id (set at checkout —
        // see PaymentPortalController::pay()) rather than the package_name string snapshot,
        // which can drift from the plan's current name.
        $packageBreakdown = Transaction::where('status', 'success')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->whereNotNull('plan_id')
            ->selectRaw('plan_id, package_name, COUNT(*) as sales_count, SUM(amount) as revenue')
            ->groupBy('plan_id', 'package_name')
            ->orderByDesc('revenue')
            ->limit(7)
            ->get();

        $packagePlanTypes = Plan::whereIn('id', $packageBreakdown->pluck('plan_id'))
            ->get()
            ->mapWithKeys(fn ($plan) => [$plan->name => $plan->type]);

        $currency = Auth::user()->tenant?->currency_symbol ?? 'KES';

        $incomeMonthDelta = $stats['income_last_month'] > 0
            ? (($stats['income_month'] - $stats['income_last_month']) / $stats['income_last_month']) * 100
            : null;

        return view('dashboard.oneisp', compact(
            'stats', 'chartLabels', 'chartData', 'growthLabels', 'growthData', 'currency',
            'incomeMonthDelta', 'subscriptionsThisMonth', 'subscriptionsSparkline',
            'packageBreakdown', 'packagePlanTypes'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            'customers_total' => HotspotUser::count() + PppoeUser::count(),
            'pppoe_expired' => PppoeUser::where('status', 'expired')->count(),
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

    /**
     * Revenue Report's time-range filter (dashboard/partials/_oneisp-revenue-chart.blade.php) —
     * fetched by JS and swapped into the existing Chart.js instance, not a page reload.
     * "Expense" is always 0 (see the chart partial's own comment on why), but returned as a
     * same-length array so the chart's second dataset doesn't need special-casing client-side.
     */
    public function revenueChartData(Request $request)
    {
        $range = in_array($request->get('range'), ['today', 'this_week', 'this_month', 'this_year', 'last_year'], true)
            ? $request->get('range')
            : 'this_month';

        $now = Carbon::now();
        $labels = [];
        $data = [];

        switch ($range) {
            case 'today':
                for ($h = 0; $h < 24; $h++) {
                    $start = $now->copy()->startOfDay()->addHours($h);
                    $labels[] = $start->format('ga');
                    $data[] = (float) Transaction::whereBetween('created_at', [$start, $start->copy()->addHour()])
                        ->where('status', 'success')->sum('amount');
                }
                break;

            case 'this_week':
                $start = $now->copy()->startOfWeek();
                for ($i = 0; $i < 7; $i++) {
                    $day = $start->copy()->addDays($i);
                    $labels[] = $day->format('D');
                    $data[] = (float) Transaction::whereDate('created_at', $day)->where('status', 'success')->sum('amount');
                }
                break;

            case 'this_month':
                for ($d = 1; $d <= $now->daysInMonth; $d++) {
                    $day = $now->copy()->startOfMonth()->addDays($d - 1);
                    $labels[] = (string) $d;
                    $data[] = (float) Transaction::whereDate('created_at', $day)->where('status', 'success')->sum('amount');
                }
                break;

            case 'this_year':
            case 'last_year':
                $year = $range === 'last_year' ? $now->year - 1 : $now->year;
                for ($m = 1; $m <= 12; $m++) {
                    $labels[] = Carbon::create($year, $m, 1)->format('M');
                    $data[] = (float) Transaction::whereMonth('created_at', $m)->whereYear('created_at', $year)
                        ->where('status', 'success')->sum('amount');
                }
                break;
        }

        return response()->json([
            'labels' => $labels,
            'earning' => $data,
            'expense' => array_fill(0, count($data), 0),
        ]);
    }
}

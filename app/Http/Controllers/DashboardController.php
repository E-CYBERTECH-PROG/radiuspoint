<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

/**
 * TEMPORARY: every figure below is hardcoded mock data, not a database query — the
 * dashboard UI was built ahead of the real backend wiring for it. When that's ready, swap
 * this method's body for real queries against Transaction/HotspotUser/PppoeUser/Plan/radacct
 * (git history has the previous DB-backed version, or see App\Console\Commands\SeedDemoData
 * for the shape realistic seeded data takes) — the view/partials themselves don't need to
 * change, they just read whatever variables this action passes them.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $stats = [
            'income_today' => 4198.0,
            'income_month' => 569883,
            'income_last_month' => 574480,
            'hotspot_active' => 200,
            'pppoe_active' => 450,
            'online_now' => 557,
            'customers_total' => 1324,
            'customers_expired' => 674,
            'online_ppp' => 557,
            'online_hotspot' => 0,
        ];

        $chartLabels = [];
        $chartValues = [850000, 920000, 780000, 860000, 574480, 569883];
        for ($i = 5; $i >= 0; $i--) {
            $chartLabels[] = $now->copy()->subMonths($i)->format('M');
        }
        $chartData = $chartValues;

        // Same 6-month window as the revenue chart above, so the two charts line up.
        $growthLabels = $chartLabels;
        $growthData = [48, 52, 45, 50, 47, 53];

        $subscriptionsThisMonth = 374;
        $subscriptionsSparkline = [62, 58, 65, 60, 40, 34];

        $packageBreakdown = collect([
            ['package_name' => '20 Mbps - F', 'sales_count' => 136, 'revenue' => 190264],
            ['package_name' => '35 Mbps - F', 'sales_count' => 64, 'revenue' => 108736],
            ['package_name' => 'Faiba 4 Mbps', 'sales_count' => 67, 'revenue' => 80400],
            ['package_name' => 'Faiba 10 Mbps', 'sales_count' => 36, 'revenue' => 54000],
            ['package_name' => '18 MbpsS-S', 'sales_count' => 13, 'revenue' => 26000],
            ['package_name' => '60 Mbps - F', 'sales_count' => 11, 'revenue' => 25289],
            ['package_name' => '5 MBPS_N', 'sales_count' => 12, 'revenue' => 18000],
        ])->map(fn ($row) => (object) $row);

        $packagePlanTypes = collect($packageBreakdown)->mapWithKeys(fn ($row) => [$row->package_name => 'ppp']);

        $currency = 'KES';

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

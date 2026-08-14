<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\MpesaSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // 1. Core Financial & Network Metrics
        $stats['income_today'] = Transaction::whereDate('created_at', $now->copy()->startOfDay())
                                            ->where('status', 'success')->sum('amount');

        $stats['income_yesterday'] = Transaction::whereDate('created_at', $now->copy()->subDay())
                                            ->where('status', 'success')->sum('amount');

        $stats['income_month'] = Transaction::whereMonth('created_at', $now->month)
                                            ->whereYear('created_at', $now->year)
                                            ->where('status', 'success')->sum('amount');

        $lastMonth = $now->copy()->subMonth();
        $stats['income_last_month'] = Transaction::whereMonth('created_at', $lastMonth->month)
                                            ->whereYear('created_at', $lastMonth->year)
                                            ->where('status', 'success')->sum('amount');

        $stats['hotspot_active'] = HotspotUser::where('status', 'active')->count();
        $stats['pppoe_active'] = PppoeUser::where('status', 'active')->count();
        $stats['routers_offline'] = Router::where('status', 'offline')->count();
        $stats['open_tickets'] = Ticket::where('status', 'open')->count();
        $stats['online_now'] = $this->liveOnlineCount();

        $stats['mpesa_status'] = $this->mpesaStatusSnapshot();

        // 2. Recent Transactions (Latest 5 for the data table)
        $recentTransactions = $this->recentTransactionsSnapshot();

        // 3. Expiring Soon Watchlist (Hotspot + PPPoE users expiring in the next 24 hours)
        $expiringHotspot = HotspotUser::where('status', 'active')
            ->whereBetween('expires_at', [$now, $now->copy()->addHours(24)])
            ->get()
            ->map(fn ($user) => (object) ['label' => $user->phone_number, 'expires_at' => $user->expires_at]);

        $expiringPppoe = PppoeUser::where('status', 'active')
            ->whereBetween('expires_at', [$now, $now->copy()->addHours(24)])
            ->get()
            ->map(fn ($user) => (object) ['label' => $user->username, 'expires_at' => $user->expires_at]);

        $expiringUsers = $expiringHotspot->concat($expiringPppoe)
            ->sortBy('expires_at')
            ->take(4)
            ->values();

        // 4. Real revenue-by-month data for the chart (last 6 months, no more fabricated numbers)
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

        // 5. Real router status list (replaces the old fabricated "Recent Router Logs" panel)
        $routers = $this->routerStatusSnapshot();

        // 6. New customers per month, last 6 months — hotspot + PPPoE combined, same window as
        // the revenue chart so the two read together (are we growing, and is it paying off).
        $growthLabels = [];
        $growthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $growthLabels[] = $month->format('M');
            $growthData[] = HotspotUser::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count()
                + PppoeUser::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
        }

        // 7. Most-purchased packages — same query Reports > Analytics uses, capped to the top 5
        // so it fits a dashboard tile rather than duplicating that page's full top-10 table.
        $topPackages = Transaction::where('status', 'success')
            ->selectRaw('package_name, COUNT(*) as purchase_count')
            ->groupBy('package_name')
            ->orderByDesc('purchase_count')
            ->limit(5)
            ->get();

        // 8. View-layer values every layout needs — computed once here instead of duplicated as
        // a @php block at the top of each of the 3 layout templates.
        $currency = Auth::user()->tenant?->currency_symbol ?? 'KES';
        $currentTimezone = Auth::user()->tenant?->timezone ?? config('app.timezone');

        $incomeTodayDelta = ($stats['income_yesterday'] ?? 0) > 0
            ? (($stats['income_today'] - $stats['income_yesterday']) / $stats['income_yesterday']) * 100
            : null;
        $incomeMonthDelta = ($stats['income_last_month'] ?? 0) > 0
            ? (($stats['income_month'] - $stats['income_last_month']) / $stats['income_last_month']) * 100
            : null;

        $dashboardInitial = [
            'online_now' => (int) ($stats['online_now'] ?? 0),
            'recent_transactions' => $recentTransactions,
            'routers' => $routers,
            'mpesa_status' => $stats['mpesa_status'],
        ];

        // Which of the 3 arrangements this user picked in Profile > Appearance — falls back to
        // 'standard' for garbage/unrecognized values rather than a missing-view error.
        $layout = in_array(Auth::user()->dashboard_layout, \App\Models\User::DASHBOARD_LAYOUTS, true)
            ? Auth::user()->dashboard_layout
            : 'standard';

        return view("dashboard.{$layout}", compact(
            'stats', 'recentTransactions', 'expiringUsers', 'chartLabels', 'chartData', 'routers',
            'growthLabels', 'growthData', 'topPackages', 'currency', 'currentTimezone',
            'incomeTodayDelta', 'incomeMonthDelta', 'dashboardInitial'
        ));
    }

    private function liveOnlineCount(): int
    {
        $routerIps = Router::where('tenant_id', Auth::user()->tenant_id)->pluck('ip_address');

        if ($routerIps->isEmpty()) {
            return 0;
        }

        return DB::table('radacct')
            ->whereIn('nasipaddress', $routerIps)
            ->whereNull('acctstoptime')
            ->count();
    }

    /**
     * Polled by the dashboard every 10s — a single combined snapshot (online count, recent
     * transactions, router status, M-Pesa health) so the page makes one request per tick
     * instead of two separate timers hitting the server at different intervals.
     */
    public function liveSnapshot(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'online_now' => $this->liveOnlineCount(),
            'recent_transactions' => $this->recentTransactionsSnapshot(),
            'routers' => $this->routerStatusSnapshot(),
            'mpesa_status' => $this->mpesaStatusSnapshot(),
        ]);
    }

    /**
     * Three real states, not the old two-state "Active/Not Configured" that never reflected an
     * actual outage — MpesaService::stkPush() tracks consecutive_failures on every real attempt,
     * so "degraded" here means real customer payments have been failing repeatedly, not a guess.
     */
    /**
     * Reflects both gateways, not just the primary — if slot 1 is down but slot 2 (backup) is
     * healthy, customers can still pay, so this reports "active" (with a note) rather than
     * "degraded", matching what PaymentPortalController::pay() actually does at checkout time.
     */
    private function mpesaStatusSnapshot(): array
    {
        $settings = MpesaSetting::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('slot', [1, 2])
            ->orderBy('slot')
            ->get();

        $active = $settings->filter(fn ($s) => $s->is_active);

        if ($active->isEmpty()) {
            return ['state' => 'not_configured', 'label' => 'Not Configured'];
        }

        $primary = $active->firstWhere('slot', 1);
        $primaryDown = $primary && $primary->consecutive_failures >= 3;
        $backup = $active->firstWhere('slot', 2);
        $backupHealthy = $backup && $backup->consecutive_failures < 3;

        if (! $primaryDown) {
            return ['state' => 'active', 'label' => 'Active'];
        }

        if ($backupHealthy) {
            return ['state' => 'active', 'label' => 'Active (Backup)'];
        }

        return ['state' => 'degraded', 'label' => 'Degraded'];
    }

    private function recentTransactionsSnapshot()
    {
        return Transaction::latest()->take(5)->get()->map(fn ($t) => [
            'customer_name' => $t->customer_name,
            'phone_number' => $t->phone_number,
            'package_name' => $t->package_name,
            'amount' => (float) $t->amount,
            'payment_method' => $t->payment_method,
            'status' => $t->status,
            'created_at_human' => $t->created_at->diffForHumans(),
            'hotspot_user_id' => $t->hotspot_user_id,
        ]);
    }

    private function routerStatusSnapshot()
    {
        return Router::latest()->take(5)->get()->map(fn ($r) => [
            'name' => $r->name,
            'status' => $r->status,
        ]);
    }
}

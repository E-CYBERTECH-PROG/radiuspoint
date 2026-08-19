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

        $recentTransactions = $this->recentTransactionsSnapshot();

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

        $routers = $this->routerStatusSnapshot();

        // Same 6-month window as the revenue chart above, so the two charts line up.
        $growthLabels = [];
        $growthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $growthLabels[] = $month->format('M');
            $growthData[] = HotspotUser::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count()
                + PppoeUser::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
        }

        // Reports > Analytics runs the same query for its top-10 view.
        $topPackages = Transaction::where('status', 'success')
            ->selectRaw('package_name, COUNT(*) as purchase_count')
            ->groupBy('package_name')
            ->orderByDesc('purchase_count')
            ->limit(5)
            ->get();

        // Computed once here rather than in each of the 3 layout templates.
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

        // User's dashboard layout choice from Profile > Appearance; falls back to 'standard'
        // for invalid values.
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
     * Polled by the dashboard every 10s; combines online count, recent transactions,
     * router status, and M-Pesa health into a single response.
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
     * Reports 'not_configured', 'active', or 'degraded' based on consecutive_failures on each
     * M-Pesa slot. If the primary is down but the backup is healthy, still reports 'active',
     * matching what PaymentPortalController::pay() does at checkout.
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

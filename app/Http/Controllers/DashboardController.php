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

        return view('dashboard', compact(
            'stats', 'recentTransactions', 'expiringUsers', 'chartLabels', 'chartData', 'routers'
        ));
    }

    /**
     * Polled by the "Online Right Now" dashboard widget every few seconds. Counts truly-live
     * RADIUS sessions (radacct rows with no stop time yet) rather than the `status` column on
     * HotspotUser/PppoeUser — status reflects billing state (active package vs expired), not
     * whether the customer is actually connected to a router right this second.
     */
    public function liveCount(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['count' => $this->liveOnlineCount()]);
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
     * Polled by the dashboard's Recent Transactions / Router Status widgets every 10s. Reuses
     * the exact same two snapshot queries the page-load render already uses, so the initial
     * Blade-rendered markup and every subsequent poll are always the same shape — one render
     * path, not two that could silently drift apart.
     */
    public function liveSnapshot(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
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
    private function mpesaStatusSnapshot(): array
    {
        $setting = MpesaSetting::where('tenant_id', Auth::user()->tenant_id)->first();

        if (! $setting || ! $setting->is_active) {
            return ['state' => 'not_configured', 'label' => 'Not Configured'];
        }

        if ($setting->consecutive_failures >= 3) {
            return ['state' => 'degraded', 'label' => 'Degraded'];
        }

        return ['state' => 'active', 'label' => 'Active'];
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

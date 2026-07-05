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

        $mpesaSetting = MpesaSetting::where('tenant_id', Auth::user()->tenant_id)->first();
        $stats['mpesa_active'] = $mpesaSetting?->is_active ?? false;

        // 2. Recent Transactions (Latest 5 for the data table)
        $recentTransactions = Transaction::latest()->take(5)->get();

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
        $routers = Router::latest()->take(5)->get();

        return view('dashboard', compact(
            'stats', 'recentTransactions', 'expiringUsers', 'chartLabels', 'chartData', 'routers'
        ));
    }
}

<?php

namespace App\Http\Controllers;

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
        $users = PppoeUser::where('tenant_id', Auth::user()->tenant_id)
            ->when($request->filled('search'), fn ($q) => $q->where('username', 'like', "%{$request->search}%"))
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

        $logs = DB::table('radacct')
            ->whereIn('nasipaddress', $routers->pluck('ip_address'))
            ->when($request->filled('search'), fn ($q) => $q->where('username', 'like', "%{$request->search}%"))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('acctstarttime', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('acctstarttime', '<=', $request->to))
            ->orderByDesc('acctstarttime')
            ->paginate($this->perPage($request, 50))
            ->withQueryString();

        return view('reports.access-log', compact('logs', 'routersByIp'));
    }
}

<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\Transaction;
use Illuminate\View\View;

/**
 * The platform admin's landing page — before this existed, logging in as a platform admin sent
 * you straight to the bare tenant list (see AuthenticatedSessionController::store()) with no
 * overview at all. Everything here is cross-tenant by design (withoutGlobalScope throughout),
 * and excludes RadiusPoint's own internal tenant (config('billing.platform_tenant_id')) from
 * every count/sum — it's a staff container, not a real ISP customer.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $platformTenantId = config('billing.platform_tenant_id');
        $now = now();

        $realTenants = Tenant::where('id', '!=', $platformTenantId);

        $tenantStats = [
            'total' => (clone $realTenants)->count(),
            'active' => (clone $realTenants)->where('status', 'active')->count(),
            'pending' => (clone $realTenants)->where('status', 'pending')->count(),
            'suspended' => (clone $realTenants)->where('status', 'suspended')->count(),
            'trial' => (clone $realTenants)->where('subscription_status', 'trial')->count(),
            'new_this_month' => (clone $realTenants)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
        ];

        $revenueThisMonth = $this->platformRevenue($now->month, $now->year, $platformTenantId);
        $lastMonth = $now->copy()->subMonthNoOverflow();
        $revenueLastMonth = $this->platformRevenue($lastMonth->month, $lastMonth->year, $platformTenantId);

        $revenueDelta = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : null;

        $commissionStats = [
            'collected_this_month' => TenantInvoice::withoutGlobalScope('tenant')
                ->where('status', 'paid')
                ->whereMonth('paid_at', $now->month)->whereYear('paid_at', $now->year)
                ->sum('amount_due'),
            'outstanding' => TenantInvoice::withoutGlobalScope('tenant')->where('status', 'pending')->sum('amount_due'),
            'overdue_count' => TenantInvoice::withoutGlobalScope('tenant')->where('status', 'pending')->where('due_at', '<', $now)->count(),
        ];

        // Last 6 calendar months, oldest first — same window for both charts so they read
        // together at a glance.
        $months = collect(range(5, 0))->map(fn ($i) => $now->copy()->subMonthsNoOverflow($i)->startOfMonth());

        $tenantGrowth = $months->map(fn ($m) => [
            'label' => $m->format('M Y'),
            'count' => (clone $realTenants)->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->count(),
        ]);

        $revenueTrend = $months->map(fn ($m) => [
            'label' => $m->format('M Y'),
            'total' => $this->platformRevenue($m->month, $m->year, $platformTenantId),
        ]);

        $recentSignups = (clone $realTenants)->latest()->limit(6)->get();

        // "At risk" = billing-locked (overdue past grace) OR trial ending within 3 days with no
        // paid subscription yet — the two ways a tenant is about to lose (or has lost) access.
        $atRiskTenantIds = TenantInvoice::withoutGlobalScope('tenant')
            ->where('status', 'pending')->where('due_at', '<', $now)
            ->pluck('tenant_id');

        $trialEndingSoonIds = (clone $realTenants)
            ->where('subscription_status', 'trial')
            ->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [$now, $now->copy()->addDays(3)])
            ->pluck('id');

        $atRiskTenants = (clone $realTenants)
            ->whereIn('id', $atRiskTenantIds->merge($trialEndingSoonIds)->unique())
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'tenant' => $t,
                'reason' => $atRiskTenantIds->contains($t->id) ? 'overdue' : 'trial_ending',
            ]);

        $topTenants = (clone $realTenants)
            ->where('status', 'active')
            ->get()
            ->map(fn ($t) => [
                'tenant' => $t,
                'revenue' => $this->tenantScoped(Transaction::class, $t->id)
                    ->where('status', 'success')
                    ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
                    ->sum('amount'),
            ])
            ->filter(fn ($row) => $row['revenue'] > 0)
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        return view('platform-admin.dashboard', compact(
            'tenantStats', 'revenueThisMonth', 'revenueDelta', 'commissionStats',
            'tenantGrowth', 'revenueTrend', 'recentSignups', 'atRiskTenants', 'topTenants'
        ));
    }

    private function platformRevenue(int $month, int $year, int $excludeTenantId): float
    {
        return (float) Transaction::withoutGlobalScope('tenant')
            ->where('tenant_id', '!=', $excludeTenantId)
            ->where('status', 'success')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('amount');
    }

    private function tenantScoped(string $modelClass, int $tenantId)
    {
        return $modelClass::withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }
}

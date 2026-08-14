<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Notifications\TenantApproved;
use App\Notifications\TenantStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $search = $this->searchTerm($request);

        $tenants = Tenant::query()
            ->when($search, fn ($q) => $q->where('company_name', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('tier'), fn ($q) => $q->where('subscription_tier', $request->tier))
            ->with('users')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('platform-admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load('users');

        $stats = [
            'routers' => $this->tenantScoped(Router::class, $tenant)->count(),
            'pppoe_active' => $this->tenantScoped(PppoeUser::class, $tenant)->where('status', 'active')->count(),
            'pppoe_total' => $this->tenantScoped(PppoeUser::class, $tenant)->count(),
            'hotspot_active' => $this->tenantScoped(HotspotUser::class, $tenant)->where('status', 'active')->count(),
            'hotspot_total' => $this->tenantScoped(HotspotUser::class, $tenant)->count(),
            'plans' => $this->tenantScoped(Plan::class, $tenant)->count(),
            'revenue_this_month' => $this->tenantScoped(Transaction::class, $tenant)
                ->where('status', 'success')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'revenue_lifetime' => $this->tenantScoped(Transaction::class, $tenant)->where('status', 'success')->sum('amount'),
            'open_tickets' => $this->tenantScoped(Ticket::class, $tenant)->where('status', 'open')->count(),
        ];

        $recentTransactions = $this->tenantScoped(Transaction::class, $tenant)->latest()->limit(10)->get();
        $recentActivity = AdminActivityLog::where('tenant_id', $tenant->id)->with('admin')->latest()->limit(10)->get();
        $invoices = $this->tenantScoped(TenantInvoice::class, $tenant)->latest('period_start')->get();

        return view('platform-admin.tenants.show', compact('tenant', 'stats', 'recentTransactions', 'recentActivity', 'invoices'));
    }

    public function edit(Tenant $tenant): View
    {
        return view('platform-admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255|unique:tenants,company_name,'.$tenant->id,
            'subdomain' => 'nullable|string|max:255|unique:tenants,subdomain,'.$tenant->id,
            'subscription_tier' => 'required|in:free,starter,pro',
            'subscription_status' => 'required|in:trial,active,expired,cancelled',
            'subscription_expires_at' => 'nullable|date',
            'admin_notes' => 'nullable|string',
        ]);

        $tenant->update($validated);

        AdminActivityLog::record($request->user(), $tenant, 'updated', 'Updated tenant details/subscription');

        return redirect()->route('platform-admin.tenants.show', $tenant)->with('success', "{$tenant->company_name} has been updated.");
    }

    public function approve(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'active']);

        $tenant->users->first()?->notify(new TenantApproved());

        AdminActivityLog::record($request->user(), $tenant, 'approved');

        return redirect()->route('platform-admin.tenants.index')
            ->with('success', "{$tenant->company_name} has been approved.");
    }

    public function reject(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'rejected']);

        AdminActivityLog::record($request->user(), $tenant, 'rejected');

        return redirect()->route('platform-admin.tenants.index')
            ->with('success', "{$tenant->company_name} has been rejected.");
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'suspended']);

        $tenant->users->first()?->notify(new TenantStatusChanged('suspended'));

        AdminActivityLog::record($request->user(), $tenant, 'suspended');

        return redirect()->route('platform-admin.tenants.index')
            ->with('success', "{$tenant->company_name} has been suspended.");
    }

    public function reactivate(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'active']);

        $tenant->users->first()?->notify(new TenantStatusChanged('active'));

        AdminActivityLog::record($request->user(), $tenant, 'reactivated');

        return redirect()->route('platform-admin.tenants.index')
            ->with('success', "{$tenant->company_name} has been reactivated.");
    }

    private function tenantScoped(string $modelClass, Tenant $tenant)
    {
        return $modelClass::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id);
    }
}

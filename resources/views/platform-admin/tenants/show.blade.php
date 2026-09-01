<x-sidebar-layout title="{{ $tenant->company_name }}">
    <div class="mb-4">
        <a href="{{ route('platform-admin.tenants.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to Tenants
        </a>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
            <div>
                <h1 class="mb-1">{{ $tenant->company_name }}</h1>
                <p class="text-muted mb-0">
                    @if($owner = $tenant->users->first())
                        {{ $owner->name }} &middot; {{ $owner->email }}
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('platform-admin.tenants.export-data', $tenant) }}" class="btn">
                    <i class="ti ti-download icon"></i> Export This Tenant's Data
                </a>
                <a href="{{ route('platform-admin.tenants.edit', $tenant) }}" class="btn btn-primary">
                    <i class="ti ti-edit icon"></i> Edit
                </a>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Routers</p>
                <h3 class="font-monospace fw-bold mb-0">{{ $stats['routers'] }}</h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">PPPoE Users</p>
                <h3 class="font-monospace fw-bold mb-0">{{ $stats['pppoe_active'] }} <span class="fs-6 fw-normal text-muted">/ {{ $stats['pppoe_total'] }}</span></h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Hotspot Users</p>
                <h3 class="font-monospace fw-bold mb-0">{{ $stats['hotspot_active'] }} <span class="fs-6 fw-normal text-muted">/ {{ $stats['hotspot_total'] }}</span></h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Open Tickets</p>
                <h3 class="font-monospace fw-bold mb-0">{{ $stats['open_tickets'] }}</h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Revenue This Month</p>
                <h3 class="font-monospace fw-bold text-success mb-0">KES {{ number_format($stats['revenue_this_month']) }}</h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Lifetime Revenue</p>
                <h3 class="font-monospace fw-bold text-success mb-0">KES {{ number_format($stats['revenue_lifetime']) }}</h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Plans</p>
                <h3 class="font-monospace fw-bold mb-0">{{ $stats['plans'] }}</h3>
            </div></div>
        </div>
        <div class="col">
            <div class="card card-sm"><div class="card-body">
                <p class="text-uppercase text-muted small fw-bold mb-1">Subscription</p>
                <h3 class="fw-bold text-capitalize mb-0">{{ $tenant->subscription_tier }} &middot; {{ $tenant->subscription_status }}</h3>
                @if($tenant->subscription_expires_at)
                    <p class="text-muted small mt-1 mb-0">Expires {{ $tenant->subscription_expires_at->format('d M Y') }}</p>
                @endif
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Recent Transactions</h3>
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                    <tr>
                                        <td class="fw-bold">{{ $transaction->customer_name }}</td>
                                        <td class="text-muted">{{ $transaction->package_name }}</td>
                                        <td class="fw-bold font-monospace">KES {{ number_format($transaction->amount) }}</td>
                                        <td>
                                            @if($transaction->status === 'success')
                                                <span class="text-success small fw-bold">Success</span>
                                            @else
                                                <span class="text-danger small fw-bold">Failed</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $transaction->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Admin Notes</h3>
                    <p class="text-muted mb-0" style="white-space:pre-line">{{ $tenant->admin_notes ?: 'No notes yet.' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h3 class="card-title mb-0">Commission Invoices</h3>
                        <a href="{{ route('platform-admin.invoices.index') }}" class="small fw-bold">View All</a>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        @forelse($invoices as $invoice)
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="fw-bold mb-0">{{ $invoice->period_start->format('F Y') }}</p>
                                    <p class="text-muted small font-monospace mb-0">KES {{ number_format($invoice->amount_due, 2) }}</p>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($invoice->status === 'paid')
                                        <x-status-badge color="green" dot>Paid</x-status-badge>
                                    @elseif($invoice->isOverdue())
                                        <x-status-badge color="red">Overdue</x-status-badge>
                                    @else
                                        <x-status-badge color="amber" icon="ti-clock">Pending</x-status-badge>
                                    @endif
                                    @if($invoice->status === 'pending')
                                        <form action="{{ route('platform-admin.invoices.mark-paid', $invoice) }}" method="POST" onsubmit="return rpConfirm(event, 'Mark this invoice as paid?')">
                                            @csrf
                                            <button type="submit" class="text-muted" style="background:none;border:0" title="Mark Paid"><i class="ti ti-circle-check"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No invoices yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Recent Activity</h3>
                    <div class="d-flex flex-column gap-3">
                        @forelse($recentActivity as $entry)
                            <div>
                                <span class="fw-bold">{{ $entry->admin?->name ?? 'System' }}</span>
                                <span class="text-muted"> {{ $entry->action }}</span>
                                <p class="text-muted small mb-0">{{ $entry->created_at->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No activity recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>

<x-sidebar-layout title="Commission Invoices">
    <div class="mb-4">
        <h1 class="mb-1">Commission Invoices</h1>
        <p class="text-muted mb-0">3% commission, billed monthly on the 1st with a 2-day grace period.</p>
    </div>

    <div class="card mb-4" style="border-radius:.5rem">
        <div class="d-flex flex-column flex-sm-row rp-stat-strip">
            <div class="flex-fill p-3">
                <p class="text-uppercase text-muted small fw-bold mb-1">Outstanding</p>
                <h3 class="font-monospace fw-bold text-warning mb-0">KES {{ number_format($totals['outstanding'], 2) }}</h3>
            </div>
            <div class="flex-fill p-3">
                <p class="text-uppercase text-muted small fw-bold mb-1">Overdue Invoices</p>
                <h3 class="font-monospace fw-bold text-danger mb-0">{{ $totals['overdue_count'] }}</h3>
            </div>
            <div class="flex-fill p-3">
                <p class="text-uppercase text-muted small fw-bold mb-1">Collected This Month</p>
                <h3 class="font-monospace fw-bold text-success mb-0">KES {{ number_format($totals['collected_this_month'], 2) }}</h3>
            </div>
        </div>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-invoices" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Period</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Amount Due</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('platform-admin.tenants.show', $invoice->tenant_id) }}">{{ $invoice->tenant->company_name }}</a>
                            </td>
                            <td class="text-muted">{{ $invoice->period_start->format('F Y') }}</td>
                            <td class="text-end text-muted font-monospace">{{ number_format($invoice->revenue_total, 2) }}</td>
                            <td class="text-end font-monospace fw-bold">{{ number_format($invoice->amount_due, 2) }}</td>
                            <td class="text-center">
                                @if($invoice->status === 'paid')
                                    <x-status-badge color="green" dot>Paid</x-status-badge>
                                @elseif($invoice->isOverdue())
                                    <x-status-badge color="red">Overdue</x-status-badge>
                                @else
                                    <x-status-badge color="amber" icon="ti-clock">Due {{ $invoice->due_at->format('d M') }}</x-status-badge>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($invoice->status === 'pending')
                                    <form action="{{ route('platform-admin.invoices.mark-paid', $invoice) }}" method="POST" onsubmit="return rpConfirm(event, 'Mark {{ $invoice->tenant->company_name }}\'s {{ $invoice->period_start->format('F Y') }} invoice (KES {{ number_format($invoice->amount_due, 2) }}) as paid?')">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm text-success p-0 text-uppercase">Mark Paid</button>
                                    </form>
                                @else
                                    <span class="text-muted small">{{ $invoice->paid_at?->format('d M Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-receipt fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No invoices match these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer">
                {{ $invoices->links('vendor.pagination.rp-circles') }}
            </div>
        @endif
        </div>

        <x-filter-modal name="invoices" :clear-url="route('platform-admin.invoices.index')">
            <div class="col-12">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                </select>
            </div>
        </x-filter-modal>
    </form>
</x-sidebar-layout>

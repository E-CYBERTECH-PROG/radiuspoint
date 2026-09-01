{{-- Expects $tenant, $invoices (paginated TenantInvoice list) in scope. --}}
<x-sidebar-layout title="Billing">

    @php
        $statusColor = [
            'active' => 'green',
            'trial' => 'primary',
            'expired' => 'red',
            'cancelled' => 'secondary',
        ][$tenant->subscription_status] ?? 'secondary';
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <p class="text-uppercase text-muted small fw-bold mb-1">Current Plan</p>
                    <h2 class="mb-0">{{ ucfirst($tenant->subscription_tier) }}</h2>
                </div>
                <span class="badge bg-{{ $statusColor }}-lt">{{ $tenant->subscription_status }}</span>
            </div>

            <div class="row g-3 py-3 border-top border-bottom">
                <div class="col-sm-6">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Renews / Expires</p>
                    <p class="fw-bold mb-0">
                        {{ $tenant->subscription_expires_at?->format('d M Y') ?? 'No expiry set' }}
                    </p>
                    @if($tenant->subscription_expires_at)
                        <p class="small mb-0 {{ $tenant->isSubscriptionExpired() ? 'text-danger' : 'text-muted' }}">
                            {{ $tenant->isSubscriptionExpired() ? 'Expired ' : 'Expires ' }}{{ $tenant->subscription_expires_at->diffForHumans() }}
                        </p>
                    @endif
                </div>
                <div class="col-sm-6">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Account Status</p>
                    <p class="fw-bold mb-0">{{ ucfirst($tenant->status) }}</p>
                </div>
            </div>

            <div class="mt-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <p class="text-muted mb-0">To upgrade, renew, or change your plan, reach out to the RadiusPoint team.</p>
                <a href="mailto:support@radiuspoint.co.ke" class="btn btn-primary flex-shrink-0">Contact Support</a>
            </div>
        </div>
    </div>

    {{-- === COMMISSION INVOICES TABLE === --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1">Commission Invoices</h3>
                <p class="text-muted small mb-0">RadiusPoint bills 3% of your monthly hotspot/PPPoE revenue, issued on the 1st of the following month with a 2-day grace period to pay.</p>
            </div>
        </div>

        <div class="card-body border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <form method="GET" class="d-flex align-items-center gap-2 text-muted small">
                <span>Show</span>
                <select name="per_page" onchange="this.form.submit()" class="form-select form-select-sm w-auto">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                <span>Entries</span>
            </form>

            <form method="GET" class="d-flex align-items-center gap-2">
                <input id="invoice-search" type="text" name="search" value="{{ request('search') }}" onchange="this.form.submit()" placeholder="e.g. March 2026" class="form-control form-control-sm">
            </form>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
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
                            <td class="fw-bold">{{ $invoice->period_start->format('F Y') }}</td>
                            <td class="text-end text-muted font-monospace">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->revenue_total, 2) }}</td>
                            <td class="text-end font-monospace fw-bold">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</td>
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
                                <a href="{{ route('billing.print', $invoice) }}" target="_blank" class="d-inline-flex align-items-center gap-1 small fw-bold" title="Print Invoice">
                                    <i class="ti ti-printer"></i> Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-receipt icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No invoices yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $invoices->links() }}</div>
    </div>
</x-sidebar-layout>

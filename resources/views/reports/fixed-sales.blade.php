<x-sidebar-layout title="Fixed Service Sales">
    <div class="mb-4">
        <h1 class="mb-1">Fixed (PPPoE) Service Sales</h1>
        <p class="text-muted mb-0">Successful transactions matched to your PPPoE packages by name.</p>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 g-3 mb-4">
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Total Sales</p>
                    <h3 class="font-monospace fw-bold mb-0">{{ number_format($totalSales) }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Total Revenue</p>
                    <h3 class="font-monospace fw-bold text-success mb-0">KES {{ number_format($totalRevenue) }}</h3>
                </div>
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
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-fixed-sales" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th class="text-end">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $transaction->customer_name }}</div>
                                <div class="text-muted small">{{ $transaction->phone_number }}</div>
                            </td>
                            <td class="text-muted">{{ $transaction->package_name }}</td>
                            <td class="font-monospace fw-bold">KES {{ number_format($transaction->amount) }}</td>
                            <td><x-status-badge color="blue">{{ $transaction->payment_method }}</x-status-badge></td>
                            <td class="text-end text-muted small">{{ $transaction->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-chart-line fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-1">No fixed service sales matched yet.</p>
                                <p class="text-muted small mb-0">Only transactions whose package name exactly matches a current PPPoE plan name are shown.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <x-filter-modal name="fixed-sales" :clear-url="route('reports.fixed-sales')">
            <div class="col-12 col-sm-6">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $transactions->links('vendor.pagination.rp-circles') }}</div>
</x-sidebar-layout>

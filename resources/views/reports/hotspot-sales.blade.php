<x-sidebar-layout title="Hotspot Service Sales">
    <div class="mb-4">
        <h1 class="mb-1">Hotspot Service Sales</h1>
        <p class="text-muted mb-0">Successful transactions matched to your Hotspot packages by name.</p>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <input type="date" name="from" value="{{ request('from') }}" class="form-control w-auto">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control w-auto">
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['from', 'to']))
            <a href="{{ route('reports.hotspot-sales') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

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

    <div class="card">
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
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-chart-line icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-1">No hotspot service sales matched yet.</p>
                                <p class="text-muted small mb-0">Only transactions whose package name exactly matches a current Hotspot plan name are shown.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
</x-sidebar-layout>

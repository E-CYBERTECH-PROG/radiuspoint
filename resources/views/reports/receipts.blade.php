<x-sidebar-layout title="Receipts">
    <div class="mb-4">
        <h1 class="mb-1">Receipts</h1>
        <p class="text-muted mb-0">Look up a payment by receipt code or date, and print it.</p>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="receipt" value="{{ request('receipt') }}" placeholder="Receipt code, e.g. SKX1234ABC..." class="form-control font-monospace">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control w-auto">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control w-auto">
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['receipt', 'from', 'to']))
            <a href="{{ route('reports.receipts') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="fw-bold font-monospace">{{ $transaction->mpesa_receipt ?: '—' }}</td>
                            <td>
                                <div class="fw-bold">{{ $transaction->customer_name }}</div>
                                <div class="text-muted small">{{ $transaction->phone_number }}</div>
                            </td>
                            <td class="text-muted">{{ $transaction->package_name }}</td>
                            <td class="font-monospace fw-bold">KES {{ number_format($transaction->amount) }}</td>
                            <td class="text-muted small">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('reports.receipts.print', $transaction) }}" target="_blank" class="fw-bold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-printer"></i> Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-receipt icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No matching receipts.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
</x-sidebar-layout>

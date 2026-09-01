<x-sidebar-layout title="Transactions">
    <div class="mb-4">
        <h1 class="mb-1">Transactions</h1>
        <p class="text-muted mb-0">Organization payments made into the system through M-Pesa/Kopokopo.</p>
    </div>

    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a href="{{ route('transactions.index', array_filter(array_merge(request()->except(['status', 'page']), ['status' => 'success']))) }}" class="nav-link {{ request('status') === 'success' ? 'active' : '' }}">
                    Success
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index', array_filter(array_merge(request()->except(['status', 'page']), ['status' => 'failed']))) }}" class="nav-link {{ request('status') === 'failed' ? 'active' : '' }}">
                    Failed
                </a>
            </li>
        </ul>
        <a href="{{ route('transactions.export', request()->query()) }}" class="btn">
            <i class="ti ti-download icon"></i> Export CSV
        </a>
    </div>

    <form method="GET" class="mb-4 d-flex flex-wrap gap-2">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="input-icon" style="min-width:14rem;flex:1">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, phone, or receipt..." class="form-control">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control w-auto">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control w-auto">
        <x-per-page-select :default="25" />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'status', 'from', 'to']))
            <a href="{{ route('transactions.index') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Receipt</th>
                        <th>Status</th>
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
                            <td class="text-muted font-monospace" data-live-receipt="{{ $transaction->id }}">{{ $transaction->mpesa_receipt ?: '—' }}</td>
                            <td data-live-status="{{ $transaction->id }}" data-status="{{ $transaction->status }}">
                                @if($transaction->status === 'success')
                                    <x-status-badge color="green" icon="ti-circle-check-filled">Success</x-status-badge>
                                @elseif($transaction->status === 'pending')
                                    <x-status-badge color="amber" icon="ti-loader-2 icon-spin">Pending</x-status-badge>
                                @else
                                    <x-status-badge color="red" icon="ti-circle-x-filled">Failed</x-status-badge>
                                @endif
                            </td>
                            <td class="text-end text-muted small">{{ $transaction->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-receipt icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No transactions recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <x-slot name="scripts">
        <script>
            (function () {
                var pendingIds = @json($transactions->where('status', 'pending')->pluck('id')->values());
                if (pendingIds.length === 0) return;

                var interval = null;
                var badges = {
                    success: '<span class="badge bg-green-lt"><i class="ti ti-circle-check-filled me-1"></i>Success</span>',
                    pending: '<span class="badge bg-yellow-lt"><i class="ti ti-loader-2 icon-spin me-1"></i>Pending</span>',
                    failed: '<span class="badge bg-red-lt"><i class="ti ti-circle-x-filled me-1"></i>Failed</span>',
                };

                async function poll() {
                    try {
                        var params = new URLSearchParams();
                        pendingIds.forEach(function (id) { params.append('ids[]', id); });
                        var res = await fetch("{{ route('transactions.live-status') }}?" + params, { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        var live = json.data || {};

                        pendingIds.forEach(function (id) {
                            var info = live[id];
                            if (!info) return;
                            var statusCell = document.querySelector('[data-live-status="' + id + '"]');
                            var receiptCell = document.querySelector('[data-live-receipt="' + id + '"]');
                            if (statusCell && info.status && badges[info.status]) statusCell.innerHTML = badges[info.status];
                            if (receiptCell && info.mpesa_receipt) receiptCell.textContent = info.mpesa_receipt;
                        });

                        // Stop polling entirely once nothing's pending anymore — a fully
                        // settled page shouldn't poll forever.
                        pendingIds = pendingIds.filter(function (id) { return (live[id] && live[id].status ? live[id].status : 'pending') === 'pending'; });
                        if (pendingIds.length === 0 && interval) clearInterval(interval);
                    } catch (e) {
                        // Transient failure — next tick retries.
                    }
                }

                poll();
                // 5s matches the dashboard's online-counter cadence — payment confirmation
                // is exactly the kind of thing worth feeling immediate.
                interval = setInterval(poll, 5000);
            })();
        </script>
    </x-slot>
</x-sidebar-layout>

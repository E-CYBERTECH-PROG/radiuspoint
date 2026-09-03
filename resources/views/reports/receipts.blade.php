<x-sidebar-layout title="Receipts">
    <div class="mb-4">
        <h1 class="mb-1">Receipts</h1>
        <p class="text-muted mb-0">Look up a payment by receipt code or date, and print it.</p>
    </div>

    {{-- === TOOLBAR + FILTERS + TABLE (one card) === --}}
    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-receipts" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="receipt" value="{{ request('receipt') }}" placeholder="Receipt code, e.g. SKX1234ABC…" class="form-control font-monospace">
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-record-payment">
                        <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">Record Payment</span>
                    </button>
                </div>
            </div>

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
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-receipt fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No matching receipts.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <x-filter-modal name="receipts" :clear-url="route('reports.receipts')">
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

    <div class="mt-3">{{ $transactions->links() }}</div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-record-payment" @if($errors->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Record Payment</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('reports.receipts.record-payment') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <p class="text-muted small">For payments received outside M-Pesa STK — cash, bank transfer, or a manual till entry.</p>

                <div class="mb-3">
                    <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" required value="{{ old('customer_name') }}" placeholder="Jane Doe" class="form-control">
                    @error('customer_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone_number" required value="{{ old('phone_number') }}" placeholder="0712345678" class="form-control">
                    @error('phone_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Package <span class="text-danger">*</span></label>
                    <input type="text" name="package_name" required value="{{ old('package_name') }}" placeholder="e.g., 10Mbps Premium Home" class="form-control">
                    @error('package_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount') }}" placeholder="0.00" class="form-control">
                    @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" required class="form-select">
                        <option value="Cash" @selected(old('payment_method') === 'Cash')>Cash</option>
                        <option value="Bank Transfer" @selected(old('payment_method') === 'Bank Transfer')>Bank Transfer</option>
                        <option value="M-Pesa (Manual)" @selected(old('payment_method') === 'M-Pesa (Manual)')>M-Pesa (Manual)</option>
                        <option value="Other" @selected(old('payment_method') === 'Other')>Other</option>
                    </select>
                    @error('payment_method') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Receipt / Reference No. <span class="text-muted text-lowercase">(optional)</span></label>
                    <input type="text" name="mpesa_receipt" value="{{ old('mpesa_receipt') }}" placeholder="Leave blank if none" class="form-control font-monospace">
                    @error('mpesa_receipt') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Save Payment</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });
        });
    </script>
</x-sidebar-layout>

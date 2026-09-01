<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt — {{ $transaction->mpesa_receipt ?: $transaction->id }}</title>
    @vite(['resources/css/app.scss'])
    <style>
        @media print {
            .no-print { display: none !important; }
            .receipt-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-body-secondary p-4">
    <div class="no-print mb-4 d-flex justify-content-between align-items-center" style="max-width:28rem;margin-inline:auto">
        <a href="{{ route('reports.receipts') }}">&larr; Back to Receipts</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="ti ti-printer icon"></i> Print
        </button>
    </div>

    <div class="receipt-card card" style="max-width:28rem;margin-inline:auto">
        <div class="card-body p-4">
            <div class="text-center pb-3 border-bottom border-dashed">
                <p class="fs-3 fw-bold mb-0">{{ auth()->user()->tenant->company_name ?? 'RadiusPoint' }}</p>
                <p class="text-muted small mb-0">Payment Receipt</p>
            </div>

            <div class="py-3 border-bottom border-dashed">
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted">Receipt No.</span>
                    <span class="font-monospace fw-bold">{{ $transaction->mpesa_receipt ?: '—' }}</span>
                </div>
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted">Date</span>
                    <span>{{ $transaction->created_at->format('d M Y H:i') }}</span>
                </div>
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted">Customer</span>
                    <span>{{ $transaction->customer_name }}</span>
                </div>
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted">Phone</span>
                    <span class="font-monospace">{{ $transaction->phone_number }}</span>
                </div>
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted">Package</span>
                    <span>{{ $transaction->package_name }}</span>
                </div>
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-muted">Method</span>
                    <span>{{ $transaction->payment_method }}</span>
                </div>
            </div>

            <div class="pt-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Amount Paid</span>
                <span class="fs-2 font-monospace fw-bold">KES {{ number_format($transaction->amount, 2) }}</span>
            </div>

            <p class="text-muted small mt-4 pt-3 border-top text-center mb-0">
                Thank you for your payment.
            </p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice — {{ $invoice->period_start->format('F Y') }}</title>
    @vite(['resources/css/app.scss'])
    <style>
        @media print {
            .no-print { display: none !important; }
            .invoice-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-body-secondary p-4">
    <div class="no-print mb-4 d-flex justify-content-between align-items-center" style="max-width:48rem;margin-inline:auto">
        <a href="{{ route('billing.edit') }}">&larr; Back to Billing</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="ti ti-printer icon"></i> Print
        </button>
    </div>

    <div class="invoice-card card" style="max-width:48rem;margin-inline:auto">
        <div class="card-body p-5">
            <div class="d-flex align-items-start justify-content-between pb-4 border-bottom">
                <div>
                    <span class="fs-2 fw-bold">RadiusPoint</span>
                    <p class="text-muted small mb-0 mt-1">Nairobi, Kenya</p>
                    <p class="text-muted small mb-0">support@radiuspoint.co.ke</p>
                </div>
                <div class="text-end">
                    <h1 class="text-uppercase mb-0">Invoice</h1>
                    <p class="text-muted small font-monospace mt-1 mb-2">#{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</p>
                    @if($invoice->status === 'paid')
                        <span class="badge bg-green-lt">Paid</span>
                    @elseif($invoice->isOverdue())
                        <span class="badge bg-red-lt">Overdue</span>
                    @else
                        <span class="badge bg-yellow-lt">Pending</span>
                    @endif
                </div>
            </div>

            <div class="row py-4 border-bottom">
                <div class="col-6">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Billed To</p>
                    <p class="fw-bold mb-0">{{ $tenant->company_name }}</p>
                    @if($tenant->support_phone)
                        <p class="text-muted small mb-0">{{ $tenant->support_phone }}</p>
                    @endif
                    @if($tenant->location)
                        <p class="text-muted small mb-0">{{ $tenant->location }}</p>
                    @endif
                </div>
                <div class="col-6 text-end">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Billing Period</p>
                    <p class="fw-bold mb-0">{{ $invoice->period_start->format('d M Y') }} &ndash; {{ $invoice->period_end->format('d M Y') }}</p>
                    <p class="text-uppercase text-muted small fw-bold mb-1 mt-3">Issue Date</p>
                    <p class="mb-0">{{ $invoice->created_at->format('d M Y') }}</p>
                    @if($invoice->status === 'paid')
                        <p class="text-uppercase text-muted small fw-bold mb-1 mt-3">Paid On</p>
                        <p class="mb-0">{{ $invoice->paid_at->format('d M Y') }}</p>
                    @else
                        <p class="text-uppercase text-muted small fw-bold mb-1 mt-3">Due Date</p>
                        <p class="mb-0 {{ $invoice->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $invoice->due_at->format('d M Y') }}</p>
                    @endif
                </div>
            </div>

            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>RadiusPoint commission — {{ $invoice->period_start->format('F Y') }}</td>
                        <td class="text-end font-monospace">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->revenue_total, 2) }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($invoice->commission_rate * 100, 2), '0'), '.') }}%</td>
                        <td class="text-end font-monospace fw-bold">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex justify-content-end mt-3">
                <div style="width:14rem">
                    <div class="d-flex justify-content-between py-2 border-top">
                        <span class="fw-bold">Total Due</span>
                        <span class="fw-bold font-monospace">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</span>
                    </div>
                </div>
            </div>

            <p class="text-muted small mt-5 pt-4 border-top text-center mb-0">
                This is a commission invoice for RadiusPoint platform usage, calculated as {{ rtrim(rtrim(number_format($invoice->commission_rate * 100, 2), '0'), '.') }}% of successful hotspot/PPPoE transaction revenue processed during the billing period above. Questions? support@radiuspoint.co.ke
            </p>
        </div>
    </div>
</body>
</html>

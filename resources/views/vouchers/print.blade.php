<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vouchers</title>
    @vite(['resources/css/app.scss'])
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-body-secondary p-4">
    <div class="no-print mb-4 d-flex justify-content-between align-items-center" style="max-width:56rem;margin-inline:auto">
        <a href="{{ route('vouchers.index') }}">&larr; Back to Vouchers</a>
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>

    <div class="row row-cols-3 g-3" style="max-width:56rem;margin-inline:auto">
        @foreach($vouchers as $voucher)
            <div class="col">
                <div class="card card-sm text-center">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-1">{{ $voucher['plan_name'] }}</p>
                        <p class="fs-3 fw-bold font-monospace my-1">{{ $voucher['code'] }}</p>
                        <p class="fw-bold mb-1">KES {{ number_format($voucher['price']) }}</p>
                        <p class="text-muted mb-0" style="font-size:.625rem">Valid {{ $voucher['validity'] }} from first use</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>

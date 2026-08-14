<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt — {{ $transaction->mpesa_receipt ?: $transaction->id }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { fira: ['"Fira Code"', 'monospace'] } } } }
    </script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .receipt-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8 font-sans text-gray-900">
    <div class="no-print mb-6 flex justify-between items-center max-w-md mx-auto">
        <a href="{{ route('reports.receipts') }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">&larr; Back to Receipts</a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-5 py-2.5 rounded-lg">
            <i class='bx bx-printer text-lg'></i> Print
        </button>
    </div>

    <div class="receipt-card bg-white border border-gray-200 rounded-xl shadow-sm max-w-md mx-auto p-8">
        <div class="text-center pb-6 border-b border-dashed border-gray-200">
            <p class="text-lg font-extrabold tracking-tight">{{ auth()->user()->tenant->company_name ?? 'RadiusPoint' }}</p>
            <p class="text-xs text-gray-400 mt-1">Payment Receipt</p>
        </div>

        <div class="py-6 border-b border-dashed border-gray-200 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Receipt No.</span>
                <span class="font-fira font-bold text-gray-900">{{ $transaction->mpesa_receipt ?: '—' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Date</span>
                <span class="text-gray-900">{{ $transaction->created_at->format('d M Y H:i') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Customer</span>
                <span class="text-gray-900">{{ $transaction->customer_name }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Phone</span>
                <span class="text-gray-900 font-fira">{{ $transaction->phone_number }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Package</span>
                <span class="text-gray-900">{{ $transaction->package_name }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Method</span>
                <span class="text-gray-900">{{ $transaction->payment_method }}</span>
            </div>
        </div>

        <div class="pt-6 flex justify-between items-center">
            <span class="text-sm font-bold text-gray-900">Amount Paid</span>
            <span class="text-2xl font-fira font-extrabold text-gray-900">KES {{ number_format($transaction->amount, 2) }}</span>
        </div>

        <p class="text-xs text-gray-400 mt-8 pt-4 border-t border-gray-100 text-center">
            Thank you for your payment.
        </p>
    </div>
</body>
</html>

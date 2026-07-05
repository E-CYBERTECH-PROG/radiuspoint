<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vouchers</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8 font-sans">
    <div class="no-print mb-6 flex justify-between items-center max-w-4xl mx-auto">
        <a href="{{ route('vouchers.index') }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">&larr; Back to Vouchers</a>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-5 py-2.5 rounded-lg">Print</button>
    </div>

    <div class="grid grid-cols-3 gap-4 max-w-4xl mx-auto">
        @foreach($vouchers as $voucher)
            <div class="bg-white border border-gray-300 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $voucher['plan_name'] }}</p>
                <p class="text-lg font-bold tracking-widest my-2" style="font-family: monospace;">{{ $voucher['code'] }}</p>
                <p class="text-sm font-bold text-gray-700">KES {{ number_format($voucher['price']) }}</p>
                <p class="text-[10px] text-gray-400 mt-1">Valid {{ $voucher['validity'] }} from first use</p>
            </div>
        @endforeach
    </div>
</body>
</html>

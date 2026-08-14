<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice — {{ $invoice->period_start->format('F Y') }}</title>
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
            .invoice-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8 font-sans text-gray-900">
    <div class="no-print mb-6 flex justify-between items-center max-w-3xl mx-auto">
        <a href="{{ route('billing.edit') }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">&larr; Back to Billing</a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-5 py-2.5 rounded-lg">
            <i class='bx bx-printer text-lg'></i> Print
        </button>
    </div>

    <div class="invoice-card bg-white border border-gray-200 rounded-xl shadow-sm max-w-3xl mx-auto p-10">
        <div class="flex items-start justify-between pb-8 border-b border-gray-100">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xl font-extrabold tracking-tight">RadiusPoint</span>
                </div>
                <p class="text-xs text-gray-400">Nairobi, Kenya</p>
                <p class="text-xs text-gray-400">support@radiuspoint.co.ke</p>
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-wide">Invoice</h1>
                <p class="text-xs text-gray-400 mt-1 font-fira">#{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</p>
                <div class="mt-2">
                    @if($invoice->status === 'paid')
                        <span class="inline-flex items-center gap-1.5 text-[10px] uppercase tracking-widest px-3 py-1 rounded-full border font-bold text-green-700 bg-green-50 border-green-200">Paid</span>
                    @elseif($invoice->isOverdue())
                        <span class="inline-flex items-center gap-1.5 text-[10px] uppercase tracking-widest px-3 py-1 rounded-full border font-bold text-red-700 bg-red-50 border-red-200">Overdue</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-[10px] uppercase tracking-widest px-3 py-1 rounded-full border font-bold text-amber-700 bg-amber-50 border-amber-200">Pending</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 py-8 border-b border-gray-100">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Billed To</p>
                <p class="text-sm font-bold text-gray-900">{{ $tenant->company_name }}</p>
                @if($tenant->support_phone)
                    <p class="text-xs text-gray-500">{{ $tenant->support_phone }}</p>
                @endif
                @if($tenant->location)
                    <p class="text-xs text-gray-500">{{ $tenant->location }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Billing Period</p>
                <p class="text-sm font-bold text-gray-900">{{ $invoice->period_start->format('d M Y') }} &ndash; {{ $invoice->period_end->format('d M Y') }}</p>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1 mt-3">Issue Date</p>
                <p class="text-sm text-gray-700">{{ $invoice->created_at->format('d M Y') }}</p>
                @if($invoice->status === 'paid')
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1 mt-3">Paid On</p>
                    <p class="text-sm text-gray-700">{{ $invoice->paid_at->format('d M Y') }}</p>
                @else
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1 mt-3">Due Date</p>
                    <p class="text-sm {{ $invoice->isOverdue() ? 'text-red-600 font-bold' : 'text-gray-700' }}">{{ $invoice->due_at->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        <table class="w-full text-left mt-8">
            <thead>
                <tr class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-200">
                    <th class="pb-3">Description</th>
                    <th class="pb-3 text-right">Revenue</th>
                    <th class="pb-3 text-right">Rate</th>
                    <th class="pb-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="py-4 text-sm text-gray-700">RadiusPoint commission — {{ $invoice->period_start->format('F Y') }}</td>
                    <td class="py-4 text-sm text-right font-fira text-gray-700">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->revenue_total, 2) }}</td>
                    <td class="py-4 text-sm text-right text-gray-700">{{ rtrim(rtrim(number_format($invoice->commission_rate * 100, 2), '0'), '.') }}%</td>
                    <td class="py-4 text-sm text-right font-fira font-bold text-gray-900">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-end mt-6">
            <div class="w-56">
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="text-sm font-bold text-gray-900">Total Due</span>
                    <span class="text-sm font-bold text-gray-900 font-fira">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</span>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-10 pt-6 border-t border-gray-100 text-center">
            This is a commission invoice for RadiusPoint platform usage, calculated as {{ rtrim(rtrim(number_format($invoice->commission_rate * 100, 2), '0'), '.') }}% of successful hotspot/PPPoE transaction revenue processed during the billing period above. Questions? support@radiuspoint.co.ke
        </p>
    </div>
</body>
</html>

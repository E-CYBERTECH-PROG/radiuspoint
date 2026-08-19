<x-sidebar-layout title="Receipts">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Receipts</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Look up a payment by receipt code or date, and print it.</p>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="receipt" value="{{ request('receipt') }}" placeholder="Receipt code, e.g. SKX1234ABC..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500 font-fira">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <input type="date" name="to" value="{{ request('to') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <x-per-page-select />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['receipt', 'from', 'to']))
            <a href="{{ route('reports.receipts') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Receipt</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Time</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 font-fira font-bold text-gray-900 dark:text-white">{{ $transaction->mpesa_receipt ?: '—' }}</td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $transaction->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $transaction->phone_number }}</p>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $transaction->package_name }}</td>
                            <td class="px-6 py-4 font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($transaction->amount) }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('reports.receipts.print', $transaction) }}" target="_blank" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 inline-flex items-center gap-1">
                                    <i class="bx bx-printer text-lg"></i> Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-receipt text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No matching receipts.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</x-sidebar-layout>

<x-sidebar-layout title="Fixed Service Sales">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fixed (PPPoE) Service Sales</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Successful transactions matched to your PPPoE packages by name.</p>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <input type="date" name="from" value="{{ request('from') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <input type="date" name="to" value="{{ request('to') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <x-per-page-select />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['from', 'to']))
            <a href="{{ route('reports.fixed-sales') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Sales</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ number_format($totalSales) }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Revenue</p>
            <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">KES {{ number_format($totalRevenue) }}</h3>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Method</th>
                        <th class="px-6 py-4 text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $transaction->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $transaction->phone_number }}</p>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $transaction->package_name }}</td>
                            <td class="px-6 py-4 font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($transaction->amount) }}</td>
                            <td class="px-6 py-4">
                                <x-status-badge color="blue">{{ $transaction->payment_method }}</x-status-badge>
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-line-chart text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No fixed service sales matched yet.</p>
                                <p class="text-xs text-gray-400 mt-2 normal-case">Only transactions whose package name exactly matches a current PPPoE plan name are shown.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</x-sidebar-layout>

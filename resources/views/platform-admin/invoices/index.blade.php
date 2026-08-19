<x-sidebar-layout title="Commission Invoices">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Commission Invoices</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">3% commission, billed monthly on the 1st with a 2-day grace period.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Outstanding</p>
            <h3 class="text-2xl font-fira font-bold text-amber-600 dark:text-amber-400">KES {{ number_format($totals['outstanding'], 2) }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Overdue Invoices</p>
            <h3 class="text-2xl font-fira font-bold text-red-600 dark:text-red-400">{{ $totals['overdue_count'] }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Collected This Month</p>
            <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">KES {{ number_format($totals['collected_this_month'], 2) }}</h3>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <select name="status" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
            <option value="">All Statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
            <option value="paid" @selected(request('status') === 'paid')>Paid</option>
        </select>
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['status']))
            <a href="{{ route('platform-admin.invoices.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Tenant</th>
                        <th class="px-6 py-4">Period</th>
                        <th class="px-6 py-4 text-right">Revenue</th>
                        <th class="px-6 py-4 text-right">Amount Due</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">
                                <a href="{{ route('platform-admin.tenants.show', $invoice->tenant_id) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $invoice->tenant->company_name }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $invoice->period_start->format('F Y') }}</td>
                            <td class="px-6 py-4 text-right text-gray-500 dark:text-gray-400 font-fira">{{ number_format($invoice->revenue_total, 2) }}</td>
                            <td class="px-6 py-4 text-right font-fira font-bold text-gray-900 dark:text-white">{{ number_format($invoice->amount_due, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($invoice->status === 'paid')
                                    <x-status-badge color="green" dot>Paid</x-status-badge>
                                @elseif($invoice->isOverdue())
                                    <x-status-badge color="red">Overdue</x-status-badge>
                                @else
                                    <x-status-badge color="amber" icon="bx-time">Due {{ $invoice->due_at->format('d M') }}</x-status-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($invoice->status === 'pending')
                                    <form action="{{ route('platform-admin.invoices.mark-paid', $invoice) }}" method="POST" onsubmit="return confirm('Mark {{ $invoice->tenant->company_name }}\'s {{ $invoice->period_start->format('F Y') }} invoice (KES {{ number_format($invoice->amount_due, 2) }}) as paid?')">
                                        @csrf
                                        <button type="submit" class="text-xs text-green-600 hover:text-green-700 font-bold uppercase tracking-wide">Mark Paid</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">{{ $invoice->paid_at?->format('d M Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-receipt text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No invoices match these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</x-sidebar-layout>

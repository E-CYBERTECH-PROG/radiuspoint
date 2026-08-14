{{-- Expects $currency in scope, and a live-updating `recentTransactions` array on the closest
     x-data="dashboard(...)" ancestor (see dashboard/partials/_scripts.blade.php). --}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm flex flex-col rp-rise h-full" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Transactions</h3>
        <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="py-3 px-2">Customer</th>
                    <th class="py-3 px-2">Package</th>
                    <th class="py-3 px-2">Amount</th>
                    <th class="py-3 px-2">Method</th>
                    <th class="py-3 px-2">Status</th>
                    <th class="py-3 px-2">Time</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700 dark:text-gray-300 divide-y divide-gray-50 dark:divide-gray-800/50">
                <template x-if="recentTransactions.length === 0">
                    <tr><td colspan="6" class="py-6 text-center text-gray-500">No recent transactions found.</td></tr>
                </template>
                <template x-for="(transaction, i) in recentTransactions" :key="i">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <td class="py-3 px-2">
                            <button type="button" x-show="transaction.hotspot_user_id" x-cloak @click="openUserPanel(transaction.hotspot_user_id)" class="font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 hover:underline text-left" x-text="transaction.customer_name"></button>
                            <p x-show="!transaction.hotspot_user_id" class="font-bold text-gray-900 dark:text-white" x-text="transaction.customer_name"></p>
                            <p class="text-xs text-gray-500" x-text="transaction.phone_number"></p>
                        </td>
                        <td class="py-3 px-2" x-text="transaction.package_name"></td>
                        <td class="py-3 px-2 font-fira font-bold" x-text="'{{ $currency }} ' + Math.round(transaction.amount).toLocaleString()"></td>
                        <td class="py-3 px-2">
                            <x-status-badge color="blue" x-text="transaction.payment_method"></x-status-badge>
                        </td>
                        <td class="py-3 px-2">
                            <x-status-badge color="green" icon="bxs-check-circle" x-show="transaction.status === 'success'">Success</x-status-badge>
                            <x-status-badge color="amber" icon="bx-loader-alt bx-spin" x-show="transaction.status === 'pending'">Pending</x-status-badge>
                            <x-status-badge color="red" icon="bxs-x-circle" x-show="transaction.status === 'failed'">Failed</x-status-badge>
                        </td>
                        <td class="py-3 px-2 text-xs text-gray-500" x-text="transaction.created_at_human"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

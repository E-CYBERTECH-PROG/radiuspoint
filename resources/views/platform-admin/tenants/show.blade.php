<x-sidebar-layout title="{{ $tenant->company_name }}">
    <div class="mb-6">
        <a href="{{ route('platform-admin.tenants.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to Tenants
        </a>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tenant->company_name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if($owner = $tenant->users->first())
                        {{ $owner->name }} &middot; {{ $owner->email }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('platform-admin.tenants.export-data', $tenant) }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <i class="bx bx-download text-lg"></i> Export This Tenant's Data
                </a>
                <a href="{{ route('platform-admin.tenants.edit', $tenant) }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    <i class="bx bx-edit-alt text-lg"></i> Edit
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Routers</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $stats['routers'] }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">PPPoE Users</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $stats['pppoe_active'] }} <span class="text-sm font-normal text-gray-400">/ {{ $stats['pppoe_total'] }}</span></h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Hotspot Users</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $stats['hotspot_active'] }} <span class="text-sm font-normal text-gray-400">/ {{ $stats['hotspot_total'] }}</span></h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Open Tickets</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $stats['open_tickets'] }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Revenue This Month</p>
            <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">KES {{ number_format($stats['revenue_this_month']) }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Lifetime Revenue</p>
            <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">KES {{ number_format($stats['revenue_lifetime']) }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Plans</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $stats['plans'] }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Subscription</p>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white capitalize">{{ $tenant->subscription_tier }} &middot; {{ $tenant->subscription_status }}</h3>
            @if($tenant->subscription_expires_at)
                <p class="text-xs text-gray-400 mt-1">Expires {{ $tenant->subscription_expires_at->format('d M Y') }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-2">Customer</th>
                            <th class="py-3 px-2">Package</th>
                            <th class="py-3 px-2">Amount</th>
                            <th class="py-3 px-2">Status</th>
                            <th class="py-3 px-2">Time</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50 dark:divide-gray-800/50">
                        @forelse($recentTransactions as $transaction)
                            <tr>
                                <td class="py-3 px-2 font-bold text-gray-900 dark:text-white">{{ $transaction->customer_name }}</td>
                                <td class="py-3 px-2 text-gray-500 dark:text-gray-400">{{ $transaction->package_name }}</td>
                                <td class="py-3 px-2 font-fira font-bold">KES {{ number_format($transaction->amount) }}</td>
                                <td class="py-3 px-2">
                                    @if($transaction->status === 'success')
                                        <span class="text-green-500 text-xs font-bold">Success</span>
                                    @else
                                        <span class="text-red-500 text-xs font-bold">Failed</span>
                                    @endif
                                </td>
                                <td class="py-3 px-2 text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Admin Notes</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $tenant->admin_notes ?: 'No notes yet.' }}</p>
            </div>

            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">Commission Invoices</h3>
                    <a href="{{ route('platform-admin.invoices.index') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800">View All</a>
                </div>
                <div class="space-y-3">
                    @forelse($invoices as $invoice)
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">{{ $invoice->period_start->format('F Y') }}</p>
                                <p class="text-xs text-gray-400 font-fira">KES {{ number_format($invoice->amount_due, 2) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($invoice->status === 'paid')
                                    <x-status-badge color="green" dot>Paid</x-status-badge>
                                @elseif($invoice->isOverdue())
                                    <x-status-badge color="red">Overdue</x-status-badge>
                                @else
                                    <x-status-badge color="amber" icon="bx-time">Pending</x-status-badge>
                                @endif
                                @if($invoice->status === 'pending')
                                    <form action="{{ route('platform-admin.invoices.mark-paid', $invoice) }}" method="POST" onsubmit="return confirm('Mark this invoice as paid?')">
                                        @csrf
                                        <button type="submit" class="text-gray-400 hover:text-green-600 transition-colors" title="Mark Paid"><i class="bx bx-check-circle text-lg"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No invoices yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Recent Activity</h3>
                <div class="space-y-3">
                    @forelse($recentActivity as $entry)
                        <div class="text-sm">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $entry->admin?->name ?? 'System' }}</span>
                            <span class="text-gray-500 dark:text-gray-400"> {{ $entry->action }}</span>
                            <p class="text-xs text-gray-400">{{ $entry->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>

<x-sidebar-layout title="Billing">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Billing</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your RadiusPoint account — customer payments are under Transactions.</p>
    </div>

    @php
        $statusStyle = [
            'active' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900/50',
            'trial' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/50',
            'expired' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/50',
            'cancelled' => 'bg-gray-50 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-800',
        ][$tenant->subscription_status] ?? 'bg-gray-50 dark:bg-gray-900/40 text-gray-500 border-gray-200';
    @endphp

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 max-w-2xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Current Plan</p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ ucfirst($tenant->subscription_tier) }}</h2>
            </div>
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide px-3 py-1.5 rounded-full border {{ $statusStyle }}">
                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $tenant->subscription_status }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-t border-b border-gray-100 dark:border-gray-800">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Renews / Expires</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $tenant->subscription_expires_at?->format('d M Y') ?? 'No expiry set' }}
                </p>
                @if($tenant->subscription_expires_at)
                    <p class="text-xs {{ $tenant->isSubscriptionExpired() ? 'text-red-500' : 'text-gray-500' }} mt-0.5">
                        {{ $tenant->isSubscriptionExpired() ? 'Expired ' : 'Expires ' }}{{ $tenant->subscription_expires_at->diffForHumans() }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Account Status</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ ucfirst($tenant->status) }}</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
            <p class="text-sm text-gray-500 dark:text-gray-400">To upgrade, renew, or change your plan, reach out to the RadiusPoint team.</p>
            <a href="mailto:support@radiuspoint.co.ke" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg transition-colors">Contact Support</a>
        </div>
    </div>

    <div class="mt-6 max-w-2xl">
        <div class="mb-3">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Commission Invoices</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">RadiusPoint bills 3% of your monthly hotspot/PPPoE revenue, issued on the 1st of the following month with a 2-day grace period to pay.</p>
        </div>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-6 py-3">Period</th>
                        <th class="px-6 py-3 text-right">Revenue</th>
                        <th class="px-6 py-3 text-right">Amount Due</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $invoice->period_start->format('F Y') }}</td>
                            <td class="px-6 py-4 text-right text-gray-500 dark:text-gray-400 font-fira">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->revenue_total, 2) }}</td>
                            <td class="px-6 py-4 text-right font-fira font-bold text-gray-900 dark:text-white">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</td>
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
                                <a href="{{ route('billing.print', $invoice) }}" target="_blank" class="text-gray-400 hover:text-blue-600 transition-colors inline-flex items-center gap-1.5 text-xs font-bold" title="Print Invoice">
                                    <i class="bx bx-printer text-base"></i> Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-receipt text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No invoices yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-sidebar-layout>

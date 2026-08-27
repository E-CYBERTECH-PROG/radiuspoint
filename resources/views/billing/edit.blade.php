{{-- Expects $tenant, $invoices (paginated TenantInvoice list) in scope. --}}
<x-sidebar-layout title="Billing">

    @php
        $statusStyle = [
            'active' => 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400',
            'trial' => 'bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400',
            'expired' => 'bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400',
            'cancelled' => 'bg-gray-100 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400',
        ][$tenant->subscription_status] ?? 'bg-gray-100 dark:bg-gray-900/40 text-gray-500';
    @endphp

    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm p-6 mb-3">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Current Plan</p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ ucfirst($tenant->subscription_tier) }}</h2>
            </div>
            <span class="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $statusStyle }}">
                {{ $tenant->subscription_status }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-t border-b border-gray-100 dark:border-gray-800">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Renews / Expires</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $tenant->subscription_expires_at?->format('d M Y') ?? 'No expiry set' }}
                </p>
                @if($tenant->subscription_expires_at)
                    <p class="text-xs {{ $tenant->isSubscriptionExpired() ? 'text-rose-500' : 'text-gray-500' }} mt-0.5">
                        {{ $tenant->isSubscriptionExpired() ? 'Expired ' : 'Expires ' }}{{ $tenant->subscription_expires_at->diffForHumans() }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Account Status</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ ucfirst($tenant->status) }}</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
            <p class="text-sm text-gray-500 dark:text-gray-400">To upgrade, renew, or change your plan, reach out to the RadiusPoint team.</p>
            <a href="mailto:support@radiuspoint.co.ke" class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg transition-colors">Contact Support</a>
        </div>
    </div>

    {{-- === COMMISSION INVOICES TABLE === --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Commission Invoices</h3>
            <p class="text-xs text-gray-400 mt-0.5">RadiusPoint bills 3% of your monthly hotspot/PPPoE revenue, issued on the 1st of the following month with a 2-day grace period to pay.</p>
        </div>

        <form method="GET" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <span>Show</span>
                <select name="per_page" onchange="this.form.submit()" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-2 py-1.5 text-gray-700 dark:text-gray-300 outline-none">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                <span>Entries</span>
            </div>

            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <label for="invoice-search">Search:</label>
                <input id="invoice-search" type="text" name="search" value="{{ request('search') }}" onchange="this.form.submit()" placeholder="e.g. March 2026" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-1.5 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-[11px] text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-3">Period</th>
                        <th class="px-6 py-3 text-right">Revenue</th>
                        <th class="px-6 py-3 text-right">Amount Due</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/60 transition-colors">
                            <td class="px-6 py-3 font-bold text-gray-900 dark:text-white">{{ $invoice->period_start->format('F Y') }}</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400 font-fira">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->revenue_total, 2) }}</td>
                            <td class="px-6 py-3 text-right font-fira font-bold text-gray-900 dark:text-white">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($invoice->status === 'paid')
                                    <x-status-badge color="green" dot>Paid</x-status-badge>
                                @elseif($invoice->isOverdue())
                                    <x-status-badge color="red">Overdue</x-status-badge>
                                @else
                                    <x-status-badge color="amber" icon="bx-time">Due {{ $invoice->due_at->format('d M') }}</x-status-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('billing.print', $invoice) }}" target="_blank" class="text-gray-400 hover:text-indigo-600 transition-colors inline-flex items-center gap-1.5 text-xs font-bold" title="Print Invoice">
                                    <i class="bx bx-printer text-base"></i> Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-receipt text-4xl mb-3 text-gray-200 dark:text-gray-800"></i>
                                <p class="text-xs tracking-widest uppercase">No invoices yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4">{{ $invoices->links() }}</div>
    </div>
</x-sidebar-layout>

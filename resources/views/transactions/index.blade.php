<x-sidebar-layout title="Transactions">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('transactions.index', array_filter(array_merge(request()->except(['status', 'page']), ['status' => 'success']))) }}"
               class="inline-flex items-center gap-1.5 text-xs uppercase tracking-widest px-4 py-2 rounded-full border font-bold whitespace-nowrap transition-colors {{ request('status') === 'success' ? 'text-white bg-green-600 border-green-600 shadow-sm shadow-green-600/30' : 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-900/50 hover:bg-green-100 dark:hover:bg-green-900/40' }}">
                <i class="bx bxs-check-circle text-sm"></i> Success
            </a>
            <a href="{{ route('transactions.index', array_filter(array_merge(request()->except(['status', 'page']), ['status' => 'failed']))) }}"
               class="inline-flex items-center gap-1.5 text-xs uppercase tracking-widest px-4 py-2 rounded-full border font-bold whitespace-nowrap transition-colors {{ request('status') === 'failed' ? 'text-white bg-red-600 border-red-600 shadow-sm shadow-red-600/30' : 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-900/50 hover:bg-red-100 dark:hover:bg-red-900/40' }}">
                <i class="bx bxs-x-circle text-sm"></i> Failed
            </a>
        </div>
        <a href="{{ route('transactions.export', request()->query()) }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg border border-gray-200 dark:border-gray-800 transition-colors">
            <i class='bx bx-download text-lg'></i> Export CSV
        </a>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3 flex-wrap">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1 min-w-[200px]">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, phone, or receipt..." class="bg-transparent border-none outline-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <input type="date" name="to" value="{{ request('to') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <x-per-page-select :default="25" />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search', 'status', 'from', 'to']))
            <a href="{{ route('transactions.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div x-data="transactionLiveStatus()" x-init="startPolling()" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Method</th>
                        <th class="px-6 py-4">Receipt</th>
                        <th class="px-6 py-4">Status</th>
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
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-fira" x-text="(live[{{ $transaction->id }}]?.mpesa_receipt ?? {{ \Illuminate\Support\Js::from($transaction->mpesa_receipt) }}) || '—'"></td>
                            <td class="px-6 py-4" x-data="{ status: {{ \Illuminate\Support\Js::from($transaction->status) }} }" x-effect="status = live[{{ $transaction->id }}]?.status ?? status">
                                <x-status-badge color="green" icon="bxs-check-circle" x-show="status === 'success'">Success</x-status-badge>
                                <x-status-badge color="amber" icon="bx-loader-alt bx-spin" x-show="status === 'pending'">Pending</x-status-badge>
                                <x-status-badge color="red" icon="bxs-x-circle" x-show="status === 'failed'">Failed</x-status-badge>
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-receipt text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No transactions recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <x-slot name="scripts">
        <script>
            function transactionLiveStatus() {
                return {
                    live: {},
                    pendingIds: @json($transactions->where('status', 'pending')->pluck('id')->values()),

                    startPolling() {
                        if (this.pendingIds.length === 0) return;
                        this.poll();
                        // 5s matches the dashboard's online-counter cadence — payment confirmation
                        // is exactly the kind of thing worth feeling immediate.
                        this.interval = setInterval(() => this.poll(), 5000);
                    },

                    async poll() {
                        try {
                            const params = new URLSearchParams();
                            this.pendingIds.forEach(id => params.append('ids[]', id));
                            const res = await fetch(`{{ route('transactions.live-status') }}?${params}`, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            this.live = json.data || {};

                            // Stop polling entirely once nothing's pending anymore — a fully
                            // settled page shouldn't poll forever.
                            this.pendingIds = this.pendingIds.filter(id => (this.live[id]?.status ?? 'pending') === 'pending');
                            if (this.pendingIds.length === 0 && this.interval) {
                                clearInterval(this.interval);
                            }
                        } catch (e) {
                            // Transient failure — next tick retries.
                        }
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

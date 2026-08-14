<x-sidebar-layout title="Search">
    <div class="mb-6">
        <form action="{{ route('search') }}" method="GET" class="relative max-w-2xl">
            <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-xl text-gray-400"></i>
            <input type="text" name="q" value="{{ $q }}" autofocus placeholder="Search phone numbers, routers, transactions…"
                   class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl py-3 pl-12 pr-4 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
        </form>
    </div>

    @if($q === '')
        <p class="text-sm text-gray-500">Type something above to search.</p>
    @else
        @php $total = $results['hotspot_users']->count() + $results['pppoe_users']->count() + $results['routers']->count() + $results['transactions']->count(); @endphp

        @if($total === 0)
            <p class="text-sm text-gray-500">No results for "{{ $q }}".</p>
        @endif

        @if($results['hotspot_users']->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Hotspot Users</h3>
                <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($results['hotspot_users'] as $user)
                        <a href="{{ route('hotspot-users.show', $user) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->phone_number }}</span>
                            <span class="text-xs text-gray-500 uppercase">{{ $user->status }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($results['pppoe_users']->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">PPPoE Users</h3>
                <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($results['pppoe_users'] as $user)
                        <a href="{{ route('pppoe-users.show', $user) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->username }}</span>
                            <span class="text-xs text-gray-500 uppercase">{{ $user->status }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($results['routers']->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Routers</h3>
                <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($results['routers'] as $router)
                        <a href="{{ route('routers.show', $router) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $router->name }}</span>
                            <span class="text-xs text-gray-500">{{ $router->ip_address }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($results['transactions']->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Transactions</h3>
                <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($results['transactions'] as $transaction)
                        <a href="{{ route('transactions.index') }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $transaction->customer_name }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ $transaction->phone_number }}</span>
                            </div>
                            <span class="text-xs font-fira font-bold text-gray-700 dark:text-gray-300">KES {{ number_format($transaction->amount) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</x-sidebar-layout>

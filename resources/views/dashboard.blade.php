<x-sidebar-layout title="Dashboard">
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', Auth::user()->name)[0] ?? Auth::user()->name;

        $incomeTodayDelta = ($stats['income_yesterday'] ?? 0) > 0
            ? (($stats['income_today'] - $stats['income_yesterday']) / $stats['income_yesterday']) * 100
            : null;
        $incomeMonthDelta = ($stats['income_last_month'] ?? 0) > 0
            ? (($stats['income_month'] - $stats['income_last_month']) / $stats['income_last_month']) * 100
            : null;
    @endphp

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ now()->format('l, d M Y') }} &middot; here's what's happening on your network.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Income Today</p>
                <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($stats['income_today'] ?? 0) }}</h3>
                @if($incomeTodayDelta !== null)
                    <span class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $incomeTodayDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                        <i class="bx {{ $incomeTodayDelta >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i> {{ number_format(abs($incomeTodayDelta), 0) }}% vs yesterday
                    </span>
                @endif
            </div>
            <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                <i class="bx bx-money text-xl"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Income This Month</p>
                <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($stats['income_month'] ?? 0) }}</h3>
                @if($incomeMonthDelta !== null)
                    <span class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $incomeMonthDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                        <i class="bx {{ $incomeMonthDelta >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i> {{ number_format(abs($incomeMonthDelta), 0) }}% vs last month
                    </span>
                @endif
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <i class="bx bx-wallet text-xl"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Active Users</p>
                <div class="flex items-baseline gap-3">
                    <div class="flex items-baseline gap-1">
                        <h3 class="text-2xl font-fira font-bold text-blue-600 dark:text-blue-400">{{ $stats['hotspot_active'] ?? 0 }}</h3>
                        <span class="text-xs text-gray-500">Hotspot</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <h3 class="text-2xl font-fira font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['pppoe_active'] ?? 0 }}</h3>
                        <span class="text-xs text-gray-500">PPPoE</span>
                    </div>
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <i class="bx bx-wifi text-xl"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Network Status</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-fira font-bold {{ ($stats['routers_offline'] ?? 0) > 0 ? 'text-red-500' : 'text-green-600 dark:text-green-400' }}">{{ $stats['routers_offline'] ?? 0 }}</h3>
                    <span class="text-xs text-gray-500">Offline Routers</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-full {{ ($stats['routers_offline'] ?? 0) > 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-500' : 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400' }} flex items-center justify-center">
                <i class="bx {{ ($stats['routers_offline'] ?? 0) > 0 ? 'bx-error' : 'bx-check-shield' }} text-xl"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <div class="lg:col-span-2 bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Transactions</h3>
                <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
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
                        @forelse($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="py-3 px-2">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $transaction->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $transaction->phone_number }}</p>
                            </td>
                            <td class="py-3 px-2">{{ $transaction->package_name }}</td>
                            <td class="py-3 px-2 font-fira font-bold">KES {{ number_format($transaction->amount) }}</td>
                            <td class="py-3 px-2">
                                <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-xs px-2 py-1 rounded-md font-bold">
                                    {{ $transaction->payment_method }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                @if($transaction->status === 'success')
                                    <span class="flex items-center gap-1 text-green-500 text-xs font-bold"><i class="bx bxs-check-circle"></i> Success</span>
                                @else
                                    <span class="flex items-center gap-1 text-red-500 text-xs font-bold"><i class="bx bxs-x-circle"></i> Failed</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-500">No recent transactions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">

            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('pppoe-users.create') }}" class="flex flex-col items-center justify-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 transition-colors group">
                        <i class="bx bx-user-plus text-2xl text-gray-400 group-hover:text-blue-600 mb-1"></i>
                        <span class="text-xs font-bold">Add User</span>
                    </a>
                    <a href="{{ route('hotspot-users.index') }}" class="flex flex-col items-center justify-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 transition-colors group">
                        <i class="bx bx-wallet text-2xl text-gray-400 group-hover:text-green-600 mb-1"></i>
                        <span class="text-xs font-bold">Recharge</span>
                    </a>
                    <a href="{{ route('tickets.index') }}" class="flex flex-col items-center justify-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 transition-colors group">
                        <i class="bx bx-support text-2xl text-gray-400 group-hover:text-purple-600 mb-1"></i>
                        <span class="text-xs font-bold">Ticket</span>
                    </a>
                    <a href="{{ route('vouchers.index') }}" class="flex flex-col items-center justify-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 transition-colors group">
                        <i class="bx bx-barcode-reader text-2xl text-gray-400 group-hover:text-orange-600 mb-1"></i>
                        <span class="text-xs font-bold">Vouchers</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('sms.index') }}" class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 rounded-xl shadow-sm text-white hover:opacity-95 transition-opacity">
                    <p class="text-[10px] font-bold text-blue-100 uppercase tracking-wider mb-1">SMS Gateway</p>
                    <h3 class="text-sm font-bold flex items-center gap-1"><i class="bx bx-message-square-dots"></i> Log Mode</h3>
                </a>
                <a href="{{ route('mpesa-settings.edit') }}" class="p-4 rounded-xl shadow-sm text-white hover:opacity-95 transition-opacity {{ $stats['mpesa_active'] ? 'bg-gradient-to-r from-green-600 to-emerald-600' : 'bg-gradient-to-r from-gray-500 to-gray-600' }}">
                    <p class="text-[10px] font-bold text-white/80 uppercase tracking-wider mb-1">M-Pesa</p>
                    <h3 class="text-sm font-bold flex items-center gap-1"><i class="bx bx-credit-card"></i> {{ $stats['mpesa_active'] ? 'Active' : 'Not Configured' }}</h3>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">Expiring Soon</h3>
                    <span class="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 text-xs px-2 py-1 rounded-full font-bold">{{ $expiringUsers->count() }} Users</span>
                </div>
                <div class="space-y-4">
                    @forelse($expiringUsers as $user)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500">
                                <i class="bx bx-user text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ $user->label }}</p>
                                <p class="text-xs text-red-500 font-bold">Expires {{ \Carbon\Carbon::parse($user->expires_at)->diffForHumans() }}</p>
                            </div>
                        </div>
                        <form action="{{ route('sms.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="phone_number" value="{{ $user->label }}">
                            <input type="hidden" name="message" value="Reminder: your internet package expires {{ \Carbon\Carbon::parse($user->expires_at)->diffForHumans() }}. Top up to stay connected.">
                            <button type="submit" class="text-gray-400 hover:text-blue-500 transition-colors" title="Send SMS Reminder">
                                <i class="bx bx-envelope text-lg"></i>
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">No users expiring in the next 24 hours.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Revenue Collections</h3>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last 6 Months</span>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Router Status</h3>
                <a href="{{ route('routers.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
            </div>
            <div class="flex-1 space-y-3">
                @forelse($routers as $router)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50 last:border-0">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $router->status === 'active' ? 'bg-green-500 animate-pulse' : ($router->status === 'offline' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $router->name }}</span>
                        </div>
                        <span class="text-xs text-gray-500 uppercase tracking-wide">{{ $router->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-6 text-center">No routers deployed yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.effect(() => {
                    const isDark = Alpine.$data(document.querySelector('[x-data]')).darkMode;
                    updateChartTheme(isDark);
                });
            });

            const ctx = document.getElementById('revenueChart').getContext('2d');
            let revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'M-Pesa (KES)',
                        data: @json($chartData),
                        backgroundColor: '#2563eb',
                        borderRadius: 4,
                        barPercentage: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { drawBorder: false },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });

            function updateChartTheme(isDark) {
                const gridColor = isDark ? '#1f2937' : '#f3f4f6';
                const textColor = isDark ? '#9ca3af' : '#6b7280';

                revenueChart.options.scales.y.grid.color = gridColor;
                revenueChart.options.scales.y.ticks.color = textColor;
                revenueChart.options.scales.x.ticks.color = textColor;
                revenueChart.update();
            }
        </script>
    </x-slot>
</x-sidebar-layout>

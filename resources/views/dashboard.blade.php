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

    {{-- Live "Online Right Now" counter: true real-time RADIUS session count (radacct rows still
         open), not the billing `status` column — reflects actual connect/disconnect activity as
         it happens. Polls every 5s; ticks a floating +/- badge in from the top on change (up for a
         new connection, dropping away below for a disconnect) so repeat visitors get something
         that visibly feels alive rather than a static number. --}}
    <div class="mb-6 bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 flex items-center justify-between gap-4"
         x-data="liveOnlineCounter({{ (int) ($stats['online_now'] ?? 0) }})" x-init="startPolling()">
        <div class="flex items-center gap-4">
            <div class="relative w-12 h-12 shrink-0 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                <span class="absolute inset-0 rounded-full bg-green-400/40 animate-ping"></span>
                <i class="bx bx-broadcast text-2xl relative"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Online Right Now</p>
                <div class="relative h-10 flex items-center" style="min-width: 4rem">
                    <span x-show="!hidden" class="text-4xl font-fira font-extrabold text-gray-900 dark:text-white tabular-nums transition-transform duration-200" :class="pulse" x-text="count"></span>
                    <span x-show="hidden" x-cloak class="text-4xl font-fira font-extrabold text-gray-300 dark:text-gray-700 tracking-widest select-none">&bull;&bull;&bull;</span>

                    <template x-for="delta in deltas" :key="delta.id">
                        <span class="absolute left-full ml-3 top-0 text-sm font-bold whitespace-nowrap"
                              :class="delta.value > 0 ? 'text-green-500 rp-delta-in' : 'text-red-500 rp-delta-out'"
                              x-text="(delta.value > 0 ? '+' : '') + delta.value"></span>
                    </template>
                </div>
                <p class="text-xs text-gray-400 mt-1">Live sessions across all your routers &middot; updates automatically</p>
            </div>
        </div>
        <button @click="toggleHidden()" type="button" title="Hide/show this number" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 shrink-0">
            <i class="bx text-2xl" :class="hidden ? 'bx-hide' : 'bx-show'"></i>
        </button>
    </div>

    <div x-data="{ moneyHidden: localStorage.getItem('rp_hide_money') === '1', toggleMoney() { this.moneyHidden = !this.moneyHidden; localStorage.setItem('rp_hide_money', this.moneyHidden ? '1' : '0'); } }" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div>
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Income Today</p>
                    <button type="button" @click="toggleMoney()" class="text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 transition-colors" title="Hide/show money values">
                        <i class="bx text-sm" :class="moneyHidden ? 'bx-hide' : 'bx-show'"></i>
                    </button>
                </div>
                <h3 x-show="!moneyHidden" class="text-2xl font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($stats['income_today'] ?? 0) }}</h3>
                <h3 x-show="moneyHidden" x-cloak class="text-2xl font-fira font-bold text-gray-300 dark:text-gray-700 tracking-widest select-none">••••••</h3>
                @if($incomeTodayDelta !== null)
                    <span x-show="!moneyHidden" class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $incomeTodayDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
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
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Income This Month</p>
                    <button type="button" @click="toggleMoney()" class="text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 transition-colors" title="Hide/show money values">
                        <i class="bx text-sm" :class="moneyHidden ? 'bx-hide' : 'bx-show'"></i>
                    </button>
                </div>
                <h3 x-show="!moneyHidden" class="text-2xl font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($stats['income_month'] ?? 0) }}</h3>
                <h3 x-show="moneyHidden" x-cloak class="text-2xl font-fira font-bold text-gray-300 dark:text-gray-700 tracking-widest select-none">••••••</h3>
                @if($incomeMonthDelta !== null)
                    <span x-show="!moneyHidden" class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $incomeMonthDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
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

    <div x-data="dashboardLiveWidgets(@json($recentTransactions), @json($routers), @json($stats['mpesa_status']))" x-init="startPolling()">
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
                        <template x-if="recentTransactions.length === 0">
                            <tr><td colspan="6" class="py-6 text-center text-gray-500">No recent transactions found.</td></tr>
                        </template>
                        <template x-for="(transaction, i) in recentTransactions" :key="i">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="py-3 px-2">
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="transaction.customer_name"></p>
                                    <p class="text-xs text-gray-500" x-text="transaction.phone_number"></p>
                                </td>
                                <td class="py-3 px-2" x-text="transaction.package_name"></td>
                                <td class="py-3 px-2 font-fira font-bold" x-text="'KES ' + Math.round(transaction.amount).toLocaleString()"></td>
                                <td class="py-3 px-2">
                                    <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-xs px-2 py-1 rounded-md font-bold" x-text="transaction.payment_method"></span>
                                </td>
                                <td class="py-3 px-2">
                                    <span x-show="transaction.status === 'success'" class="flex items-center gap-1 text-green-500 text-xs font-bold"><i class="bx bxs-check-circle"></i> Success</span>
                                    <span x-show="transaction.status === 'pending'" class="flex items-center gap-1 text-amber-500 text-xs font-bold"><i class="bx bx-loader-alt bx-spin"></i> Pending</span>
                                    <span x-show="transaction.status === 'failed'" class="flex items-center gap-1 text-red-500 text-xs font-bold"><i class="bx bxs-x-circle"></i> Failed</span>
                                </td>
                                <td class="py-3 px-2 text-xs text-gray-500" x-text="transaction.created_at_human"></td>
                            </tr>
                        </template>
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
                <a href="{{ route('mpesa-settings.edit') }}" class="p-4 rounded-xl shadow-sm text-white hover:opacity-95 transition-opacity"
                   :class="{
                       active: 'bg-gradient-to-r from-green-600 to-emerald-600',
                       degraded: 'bg-gradient-to-r from-red-600 to-orange-600',
                       not_configured: 'bg-gradient-to-r from-gray-500 to-gray-600',
                   }[mpesaStatus.state]">
                    <p class="text-[10px] font-bold text-white/80 uppercase tracking-wider mb-1">M-Pesa</p>
                    <h3 class="text-sm font-bold flex items-center gap-1">
                        <i class="bx" :class="mpesaStatus.state === 'degraded' ? 'bx-error' : 'bx-credit-card'"></i>
                        <span x-text="mpesaStatus.label"></span>
                    </h3>
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
                <template x-if="routerList.length === 0">
                    <p class="text-sm text-gray-500 py-6 text-center">No routers deployed yet.</p>
                </template>
                <template x-for="(router, i) in routerList" :key="i">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50 last:border-0">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full" :class="router.status === 'active' ? 'bg-green-500 animate-pulse' : (router.status === 'offline' ? 'bg-red-500' : 'bg-amber-500')"></span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="router.name"></span>
                        </div>
                        <span class="text-xs text-gray-500 uppercase tracking-wide" x-text="router.status"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
    </div>

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            function dashboardLiveWidgets(initialTransactions, initialRouters, initialMpesaStatus) {
                return {
                    recentTransactions: initialTransactions,
                    routerList: initialRouters,
                    mpesaStatus: initialMpesaStatus,

                    startPolling() {
                        // 10s — slower than the online counter (5s), since transaction/router
                        // status changes are meaningfully less frequent than session churn.
                        setInterval(() => this.poll(), 10000);
                    },

                    async poll() {
                        try {
                            const res = await fetch("{{ route('dashboard.live-snapshot') }}", { headers: { 'Accept': 'application/json' } });
                            const data = await res.json();
                            this.recentTransactions = data.recent_transactions;
                            this.routerList = data.routers;
                            this.mpesaStatus = data.mpesa_status;
                        } catch (e) {
                            // Transient failure — next tick retries, widgets just stay stale meanwhile.
                        }
                    },
                };
            }

            function liveOnlineCounter(initial) {
                return {
                    count: initial,
                    hidden: localStorage.getItem('rp_hide_online_count') === '1',
                    deltas: [],
                    deltaId: 0,
                    pulse: '',

                    toggleHidden() {
                        this.hidden = !this.hidden;
                        localStorage.setItem('rp_hide_online_count', this.hidden ? '1' : '0');
                    },

                    startPolling() {
                        setInterval(() => this.poll(), 5000);
                    },

                    async poll() {
                        try {
                            const res = await fetch("{{ route('dashboard.live-count') }}", { headers: { 'Accept': 'application/json' } });
                            const data = await res.json();
                            const diff = data.count - this.count;

                            if (diff !== 0) {
                                const id = this.deltaId++;
                                this.deltas.push({ id, value: diff });
                                setTimeout(() => { this.deltas = this.deltas.filter(d => d.id !== id); }, 900);

                                this.count = data.count;
                                this.pulse = diff > 0 ? 'rp-pulse-up' : 'rp-pulse-down';
                                setTimeout(() => { this.pulse = ''; }, 350);
                            }
                        } catch (e) {
                            // Silent — a missed poll just tries again in 5s.
                        }
                    },
                };
            }

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

        <style>
            /* A new connection: the +N badge drops in from above and settles near the number. */
            @keyframes rp-delta-in {
                0% { transform: translateY(-14px); opacity: 0; }
                35% { opacity: 1; }
                100% { transform: translateY(0); opacity: 0; }
            }
            /* A disconnect: the -N badge drops away below the number and fades. */
            @keyframes rp-delta-out {
                0% { transform: translateY(0); opacity: 1; }
                100% { transform: translateY(14px); opacity: 0; }
            }
            .rp-delta-in { animation: rp-delta-in 900ms ease-out forwards; }
            .rp-delta-out { animation: rp-delta-out 900ms ease-in forwards; }

            .rp-pulse-up { transform: scale(1.12); color: #16a34a; }
            .rp-pulse-down { transform: scale(0.92); color: #dc2626; }
        </style>
    </x-slot>
</x-sidebar-layout>

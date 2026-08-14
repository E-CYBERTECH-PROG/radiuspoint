<x-sidebar-layout title="Analytics">
    @php $currency = Auth::user()->tenant?->currency_symbol ?? 'KES'; @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Revenue and usage broken down by router, customer, and package — computed live, nothing fabricated.</p>
    </div>

    <div class="mb-6">
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Captive Portal Funnel <span class="text-gray-400 font-normal">— last 30 days</span></h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Portal Visits</p>
                <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ number_format($portalVisits) }}</h3>
            </div>
            <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Paid Conversions</p>
                <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">{{ number_format($portalConversions) }}</h3>
            </div>
            <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Free Mode Sessions</p>
                <h3 class="text-2xl font-fira font-bold text-blue-600 dark:text-blue-400">{{ number_format($freeModeSessions) }}</h3>
            </div>
            <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Conversion Rate</p>
                <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $conversionRate !== null ? $conversionRate.'%' : '—' }}</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Hotspot Revenue by Router</h3>
            <p class="text-xs text-gray-400 mb-4">Successful M-Pesa hotspot sales only — PPPoE customers aren't sold self-service, so they carry no router-linked transaction yet.</p>
            @if($revenueByRouter->isEmpty())
                <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-12">No hotspot sales yet.</p>
            @else
                <div style="height: 280px"><canvas id="revenueByRouterChart"></canvas></div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Accumulated Data Usage by Router</h3>
            <p class="text-xs text-gray-400 mb-4">Total data carried per router across all recorded sessions (GB), plus how many distinct customers have ever connected through it.</p>
            @if($usageByRouter->isEmpty())
                <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-12">No accounting data yet.</p>
            @else
                <div style="height: 280px"><canvas id="usageByRouterChart"></canvas></div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Top Data-Consuming Customers</h3>
            <p class="text-xs text-gray-400 mb-4">Top 10 by total GB used, across Hotspot and PPPoE.</p>
            @if($topUsers->isEmpty())
                <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-12">No accounting data yet.</p>
            @else
                <div style="height: 280px"><canvas id="topUsersChart"></canvas></div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Most Purchased Packages</h3>
            <p class="text-xs text-gray-400 mb-4">By number of successful purchases (any package type).</p>
            @if($topPackages->isEmpty())
                <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-12">No successful purchases yet.</p>
            @else
                <div style="height: 280px"><canvas id="topPackagesChart"></canvas></div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Top Customers — Detail</h3>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3 text-right">Data Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($topUsers as $u)
                        <tr>
                            <td class="px-6 py-3 font-fira font-bold text-gray-900 dark:text-white">{{ $u['username'] }}</td>
                            <td class="px-6 py-3"><x-status-badge color="blue">{{ $u['type'] }}</x-status-badge></td>
                            <td class="px-6 py-3 text-right font-fira font-bold text-gray-900 dark:text-white">{{ number_format($u['total_gb'], 2) }} GB</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 text-xs uppercase tracking-widest">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Top Packages — Detail</h3>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-3">Package</th>
                        <th class="px-6 py-3 text-center">Purchases</th>
                        <th class="px-6 py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($topPackages as $p)
                        <tr>
                            <td class="px-6 py-3 text-gray-900 dark:text-white font-bold">{{ $p->package_name }}</td>
                            <td class="px-6 py-3 text-center font-fira font-bold text-gray-900 dark:text-white">{{ number_format($p->purchase_count) }}</td>
                            <td class="px-6 py-3 text-right font-fira font-bold text-green-600 dark:text-green-400">{{ $currency }} {{ number_format($p->total_revenue) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 text-xs uppercase tracking-widest">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? '#1f2937' : '#f3f4f6';
                const textColor = isDark ? '#9ca3af' : '#6b7280';
                const baseOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor }, border: { display: false } },
                        x: { grid: { display: false }, ticks: { color: textColor }, border: { display: false } },
                    },
                };

                @if($revenueByRouter->isNotEmpty())
                new Chart(document.getElementById('revenueByRouterChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($revenueByRouter->pluck('router_name')),
                        datasets: [{
                            label: 'Revenue ({{ $currency }})',
                            data: @json($revenueByRouter->pluck('total')),
                            backgroundColor: '#2563eb',
                            borderRadius: 4,
                            barPercentage: 0.5,
                        }],
                    },
                    options: baseOptions,
                });
                @endif

                @if($usageByRouter->isNotEmpty())
                new Chart(document.getElementById('usageByRouterChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($usageByRouter->pluck('router_name')),
                        datasets: [{
                            label: 'Data (GB)',
                            data: @json($usageByRouter->pluck('total_gb')),
                            backgroundColor: '#059669',
                            borderRadius: 4,
                            barPercentage: 0.5,
                        }],
                    },
                    options: baseOptions,
                });
                @endif

                @if($topUsers->isNotEmpty())
                new Chart(document.getElementById('topUsersChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($topUsers->pluck('username')),
                        datasets: [{
                            label: 'Data (GB)',
                            data: @json($topUsers->pluck('total_gb')),
                            backgroundColor: '#d97706',
                            borderRadius: 4,
                            barPercentage: 0.5,
                        }],
                    },
                    options: { ...baseOptions, indexAxis: 'y' },
                });
                @endif

                @if($topPackages->isNotEmpty())
                new Chart(document.getElementById('topPackagesChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($topPackages->pluck('package_name')),
                        datasets: [{
                            label: 'Purchases',
                            data: @json($topPackages->pluck('purchase_count')),
                            backgroundColor: '#e11d48',
                            borderRadius: 4,
                            barPercentage: 0.5,
                        }],
                    },
                    options: { ...baseOptions, indexAxis: 'y' },
                });
                @endif
            });
        </script>
    </x-slot>
</x-sidebar-layout>

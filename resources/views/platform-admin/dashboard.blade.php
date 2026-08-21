<x-sidebar-layout title="Platform Dashboard">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Platform Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Every ISP tenant on RadiusPoint, at a glance.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('platform-admin.tenants.import-form') }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <i class="bx bx-upload text-lg"></i> Import Tenants
            </a>
            <a href="{{ route('platform-admin.tenants.index') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-buildings text-lg"></i> Manage Tenants
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Tenants</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">{{ $tenantStats['total'] }}</h3>
            <p class="text-xs text-gray-400 mt-1">+{{ $tenantStats['new_this_month'] }} this month</p>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Active</p>
            <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">{{ $tenantStats['active'] }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pending Approval</p>
            <h3 class="text-2xl font-fira font-bold {{ $tenantStats['pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $tenantStats['pending'] }}</h3>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Revenue This Month</p>
            <h3 class="text-2xl font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($revenueThisMonth) }}</h3>
            @if($revenueDelta !== null)
                <span class="inline-flex items-center gap-1 text-xs font-bold mt-0.5 {{ $revenueDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                    <i class="bx {{ $revenueDelta >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i> {{ number_format(abs($revenueDelta), 1) }}%
                </span>
            @endif
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Commission Collected</p>
            <h3 class="text-2xl font-fira font-bold text-green-600 dark:text-green-400">KES {{ number_format($commissionStats['collected_this_month']) }}</h3>
            <p class="text-xs text-gray-400 mt-1">this month</p>
        </div>
        <div class="bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Outstanding</p>
            <h3 class="text-2xl font-fira font-bold {{ $commissionStats['overdue_count'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">KES {{ number_format($commissionStats['outstanding']) }}</h3>
            @if($commissionStats['overdue_count'] > 0)
                <p class="text-xs text-red-500 mt-1">{{ $commissionStats['overdue_count'] }} overdue</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Tenant Growth</h3>
            <p class="text-xs text-gray-400 mb-4">New tenant signups by month, last 6 months.</p>
            @if($tenantGrowth->sum('count') > 0)
                <div style="height: 240px"><canvas id="tenantGrowthChart"></canvas></div>
            @else
                <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-16">No signups yet.</p>
            @endif
        </div>
        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Platform Revenue</h3>
            <p class="text-xs text-gray-400 mb-4">Total successful transaction revenue across every tenant, last 6 months.</p>
            @if($revenueTrend->sum('total') > 0)
                <div style="height: 240px"><canvas id="revenueTrendChart"></canvas></div>
            @else
                <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-16">No revenue recorded yet.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-md font-bold text-gray-900 dark:text-white">Recent Signups</h3>
                <a href="{{ route('platform-admin.tenants.index') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800">View All</a>
            </div>
            <table class="w-full text-left border-collapse">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($recentSignups as $tenant)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">{{ $tenant->company_name }}</a>
                                <p class="text-xs text-gray-400">{{ $tenant->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-3">
                                @php
                                    $statusColors = ['pending' => 'amber', 'active' => 'green', 'suspended' => 'gray', 'rejected' => 'red'];
                                @endphp
                                <x-status-badge :color="$statusColors[$tenant->status] ?? 'gray'">{{ $tenant->status }}</x-status-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if($tenant->status === 'pending')
                                    <div class="flex items-center justify-end gap-3">
                                        <form action="{{ route('platform-admin.tenants.approve', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs text-green-600 hover:text-green-700 font-bold uppercase tracking-wide">Approve</button>
                                        </form>
                                        <form action="{{ route('platform-admin.tenants.reject', $tenant) }}" method="POST" onsubmit="return rpConfirm(event, 'Reject {{ $tenant->company_name }}?')">
                                            @csrf
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-bold uppercase tracking-wide">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="bx bx-show text-lg"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-buildings text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No tenants yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">At Risk</h3>
                    <p class="text-xs text-gray-400">Overdue billing or trial ending soon.</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($atRiskTenants as $row)
                        <a href="{{ route('platform-admin.tenants.show', $row['tenant']) }}" class="flex items-center justify-between px-6 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $row['tenant']->company_name }}</span>
                            @if($row['reason'] === 'overdue')
                                <x-status-badge color="red">Overdue</x-status-badge>
                            @else
                                <x-status-badge color="amber" icon="bx-time">Trial Ending</x-status-badge>
                            @endif
                        </a>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-400">Nothing at risk right now.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">Top Tenants This Month</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($topTenants as $row)
                        <a href="{{ route('platform-admin.tenants.show', $row['tenant']) }}" class="flex items-center justify-between px-6 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $row['tenant']->company_name }}</span>
                            <span class="font-fira text-gray-500 dark:text-gray-400">KES {{ number_format($row['revenue']) }}</span>
                        </a>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-400">No revenue yet this month.</p>
                    @endforelse
                </div>
            </div>
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
                        y: { beginAtZero: true, grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor, precision: 0 }, border: { display: false } },
                        x: { grid: { display: false }, ticks: { color: textColor }, border: { display: false } },
                    },
                };

                @if($tenantGrowth->sum('count') > 0)
                new Chart(document.getElementById('tenantGrowthChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($tenantGrowth->pluck('label')),
                        datasets: [{
                            label: 'New Tenants',
                            data: @json($tenantGrowth->pluck('count')),
                            backgroundColor: '#2563eb',
                            borderRadius: 4,
                            barPercentage: 0.5,
                        }],
                    },
                    options: baseOptions,
                });
                @endif

                @if($revenueTrend->sum('total') > 0)
                new Chart(document.getElementById('revenueTrendChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($revenueTrend->pluck('label')),
                        datasets: [{
                            label: 'Revenue (KES)',
                            data: @json($revenueTrend->pluck('total')),
                            backgroundColor: '#059669',
                            borderRadius: 4,
                            barPercentage: 0.5,
                        }],
                    },
                    options: baseOptions,
                });
                @endif
            });
        </script>
    </x-slot>
</x-sidebar-layout>

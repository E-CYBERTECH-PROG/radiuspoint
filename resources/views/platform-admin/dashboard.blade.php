<x-sidebar-layout title="Platform Dashboard">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Platform Dashboard</h1>
            <p class="text-muted mb-0">Every ISP tenant on RadiusPoint, at a glance.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('platform-admin.tenants.import-form') }}" class="btn">
                <i class="ti ti-upload icon"></i> Import Tenants
            </a>
            <a href="{{ route('platform-admin.tenants.index') }}" class="btn btn-primary">
                <i class="ti ti-building icon"></i> Manage Tenants
            </a>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-lg-3 row-cols-xl-6 g-3 mb-4">
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Total Tenants</p>
                    <h3 class="font-monospace fw-bold mb-0">{{ $tenantStats['total'] }}</h3>
                    <p class="text-muted small mt-1 mb-0">+{{ $tenantStats['new_this_month'] }} this month</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Active</p>
                    <h3 class="font-monospace fw-bold text-success mb-0">{{ $tenantStats['active'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Pending Approval</p>
                    <h3 class="font-monospace fw-bold mb-0 {{ $tenantStats['pending'] > 0 ? 'text-warning' : '' }}">{{ $tenantStats['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Revenue This Month</p>
                    <h3 class="font-monospace fw-bold mb-0">KES {{ number_format($revenueThisMonth) }}</h3>
                    @if($revenueDelta !== null)
                        <span class="d-inline-flex align-items-center gap-1 small fw-bold mt-1 {{ $revenueDelta >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="ti {{ $revenueDelta >= 0 ? 'ti-trending-up' : 'ti-trending-down' }}"></i> {{ number_format(abs($revenueDelta), 1) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Commission Collected</p>
                    <h3 class="font-monospace fw-bold text-success mb-0">KES {{ number_format($commissionStats['collected_this_month']) }}</h3>
                    <p class="text-muted small mt-1 mb-0">this month</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Outstanding</p>
                    <h3 class="font-monospace fw-bold mb-0 {{ $commissionStats['overdue_count'] > 0 ? 'text-danger' : 'text-warning' }}">KES {{ number_format($commissionStats['outstanding']) }}</h3>
                    @if($commissionStats['overdue_count'] > 0)
                        <p class="text-danger small mt-1 mb-0">{{ $commissionStats['overdue_count'] }} overdue</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title mb-1">Tenant Growth</h3>
                    <p class="text-muted small mb-3">New tenant signups by month, last 6 months.</p>
                    @if($tenantGrowth->sum('count') > 0)
                        <div style="height: 240px"><canvas id="tenantGrowthChart"></canvas></div>
                    @else
                        <p class="text-center text-muted text-uppercase small py-5 mb-0">No signups yet.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title mb-1">Platform Revenue</h3>
                    <p class="text-muted small mb-3">Total successful transaction revenue across every tenant, last 6 months.</p>
                    @if($revenueTrend->sum('total') > 0)
                        <div style="height: 240px"><canvas id="revenueTrendChart"></canvas></div>
                    @else
                        <p class="text-center text-muted text-uppercase small py-5 mb-0">No revenue recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Recent Signups</h3>
                    <a href="{{ route('platform-admin.tenants.index') }}" class="small fw-bold">View All</a>
                </div>
                <table class="table card-table table-vcenter mb-0">
                    <tbody>
                        @forelse($recentSignups as $tenant)
                            <tr>
                                <td>
                                    <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="fw-bold">{{ $tenant->company_name }}</a>
                                    <p class="text-muted small mb-0">{{ $tenant->created_at->diffForHumans() }}</p>
                                </td>
                                <td>
                                    @php
                                        $statusColors = ['pending' => 'amber', 'active' => 'green', 'suspended' => 'gray', 'rejected' => 'red'];
                                    @endphp
                                    <x-status-badge :color="$statusColors[$tenant->status] ?? 'gray'">{{ $tenant->status }}</x-status-badge>
                                </td>
                                <td class="text-end">
                                    @if($tenant->status === 'pending')
                                        <div class="d-flex align-items-center justify-content-end gap-3">
                                            <form action="{{ route('platform-admin.tenants.approve', $tenant) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm text-success p-0 text-uppercase">Approve</button>
                                            </form>
                                            <form action="{{ route('platform-admin.tenants.reject', $tenant) }}" method="POST" onsubmit="return rpConfirm(event, 'Reject {{ $tenant->company_name }}?')">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-uppercase">Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="text-muted"><i class="ti ti-eye"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5">
                                    <i class="ti ti-building icon icon-lg mb-2 d-block"></i>
                                    <p class="text-uppercase small mb-0">No tenants yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title mb-0">At Risk</h3>
                        <p class="text-muted small mb-0">Overdue billing or trial ending soon.</p>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($atRiskTenants as $row)
                        <a href="{{ route('platform-admin.tenants.show', $row['tenant']) }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                            <span class="fw-bold">{{ $row['tenant']->company_name }}</span>
                            @if($row['reason'] === 'overdue')
                                <x-status-badge color="red">Overdue</x-status-badge>
                            @else
                                <x-status-badge color="amber" icon="ti-clock">Trial Ending</x-status-badge>
                            @endif
                        </a>
                    @empty
                        <p class="text-center text-muted small py-4 mb-0">Nothing at risk right now.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Top Tenants This Month</h3>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($topTenants as $row)
                        <a href="{{ route('platform-admin.tenants.show', $row['tenant']) }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                            <span class="fw-bold">{{ $row['tenant']->company_name }}</span>
                            <span class="font-monospace text-muted">KES {{ number_format($row['revenue']) }}</span>
                        </a>
                    @empty
                        <p class="text-center text-muted small py-4 mb-0">No revenue yet this month.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
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

<x-sidebar-layout title="Analytics">
    @php $currency = Auth::user()->tenant?->currency_symbol ?? 'KES'; @endphp

    <div class="mb-4">
        <h1 class="mb-1">Analytics</h1>
        <p class="text-muted mb-0">Revenue and usage broken down by router, customer, and package.</p>
    </div>

    <div class="mb-4">
        <h2 class="mb-2">Captive Portal Funnel <span class="text-muted fw-normal">— last 30 days</span></h2>
        <div class="card" style="border-radius:.5rem">
            <div class="d-flex flex-column flex-sm-row rp-stat-strip">
                <div class="flex-fill p-3">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Portal Visits</p>
                    <h3 class="font-monospace fw-bold mb-0">{{ number_format($portalVisits) }}</h3>
                </div>
                <div class="flex-fill p-3">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Paid Conversions</p>
                    <h3 class="font-monospace fw-bold text-success mb-0">{{ number_format($portalConversions) }}</h3>
                </div>
                <div class="flex-fill p-3">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Free Mode Sessions</p>
                    <h3 class="font-monospace fw-bold text-primary mb-0">{{ number_format($freeModeSessions) }}</h3>
                </div>
                <div class="flex-fill p-3">
                    <p class="text-uppercase text-muted small fw-bold mb-1">Conversion Rate</p>
                    <h3 class="font-monospace fw-bold mb-0">{{ $conversionRate !== null ? $conversionRate.'%' : '—' }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-1">Hotspot Revenue by Router</h3>
                    <p class="text-muted small mb-3">Successful M-Pesa hotspot sales only — PPPoE isn't sold self-service.</p>
                    @if($revenueByRouter->isEmpty())
                        <p class="text-center text-muted text-uppercase small py-5 mb-0">No hotspot sales yet.</p>
                    @else
                        <div style="height: 280px"><canvas id="revenueByRouterChart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-1">Accumulated Data Usage by Router</h3>
                    <p class="text-muted small mb-3">Total data (GB) and distinct customers per router, all-time.</p>
                    @if($usageByRouter->isEmpty())
                        <p class="text-center text-muted text-uppercase small py-5 mb-0">No accounting data yet.</p>
                    @else
                        <div style="height: 280px"><canvas id="usageByRouterChart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-1">Top Data-Consuming Customers</h3>
                    <p class="text-muted small mb-3">Top 10 by total GB used, across Hotspot and PPPoE.</p>
                    @if($topUsers->isEmpty())
                        <p class="text-center text-muted text-uppercase small py-5 mb-0">No accounting data yet.</p>
                    @else
                        <div style="height: 280px"><canvas id="topUsersChart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-1">Most Purchased Packages</h3>
                    <p class="text-muted small mb-3">By number of successful purchases (any package type).</p>
                    @if($topPackages->isEmpty())
                        <p class="text-center text-muted text-uppercase small py-5 mb-0">No successful purchases yet.</p>
                    @else
                        <div style="height: 280px"><canvas id="topPackagesChart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Top Customers — Detail</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Type</th>
                                <th class="text-end">Data Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topUsers as $u)
                                <tr>
                                    <td class="fw-bold font-monospace">{{ $u['username'] }}</td>
                                    <td><x-status-badge color="blue">{{ $u['type'] }}</x-status-badge></td>
                                    <td class="text-end font-monospace fw-bold">{{ number_format($u['total_gb'], 2) }} GB</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted small text-uppercase py-4">No data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Top Packages — Detail</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th class="text-center">Purchases</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPackages as $p)
                                <tr>
                                    <td class="fw-bold">{{ $p->package_name }}</td>
                                    <td class="text-center font-monospace fw-bold">{{ number_format($p->purchase_count) }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">{{ $currency }} {{ number_format($p->total_revenue) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted small text-uppercase py-4">No data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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

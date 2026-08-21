{{-- Self-contained JS/CSS for the "One ISP" layout — deliberately not sharing
     dashboard/partials/_scripts.blade.php, since that file wires up the live-poll
     `dashboard()` Alpine component this layout doesn't use (see oneisp.blade.php's
     top comment). Expects $chartLabels, $chartData, $growthLabels, $growthData,
     $subscriptionsSparkline, $packageBreakdown, $currency in scope. --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    {{-- Runs synchronously as the parser reaches it (no defer/DOMContentLoaded wrapper) — the
         canvases above are already parsed by this point, and this must register its
         'alpine:init' listener before Alpine's own deferred script fires that event,
         matching the pattern in dashboard/partials/_scripts.blade.php. --}}
    (function () {
        const sparklineBase = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: false, beginAtZero: true },
            },
        };

        const oneispSubscriptionsChart = new Chart(document.getElementById('oneispSubscriptionsChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($subscriptionsSparkline ?? []),
                datasets: [{
                    data: @json($subscriptionsSparkline ?? []),
                    backgroundColor: '#f59e0b',
                    borderRadius: 3,
                    barPercentage: 0.6,
                }],
            },
            options: sparklineBase,
        });

        const oneispProfitChart = new Chart(document.getElementById('oneispProfitChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($chartLabels ?? []),
                datasets: [{
                    data: @json($chartData ?? []),
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4,
                    fill: true,
                }],
            },
            options: sparklineBase,
        });

        const oneispRevenueChart = new Chart(document.getElementById('oneispRevenueChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($chartLabels ?? []),
                datasets: [
                    {
                        label: 'Earning',
                        data: @json($chartData ?? []),
                        backgroundColor: '#6366f1',
                        borderRadius: 4,
                        barPercentage: 0.5,
                    },
                    {
                        label: 'Expense',
                        data: @json(array_fill(0, count($chartData ?? []), 0)),
                        backgroundColor: '#fbbf24',
                        borderRadius: 4,
                        barPercentage: 0.5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } },
                },
            },
        });

        const oneispSideChart = new Chart(document.getElementById('oneispSideChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($growthLabels ?? []),
                datasets: [{
                    data: @json($growthData ?? []),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.08)',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true },
                },
            },
        });

        const oneispCharts = [oneispSubscriptionsChart, oneispProfitChart, oneispRevenueChart, oneispSideChart];

        @if($packageBreakdown->isNotEmpty())
        const oneispPackagePerformanceChart = new Chart(document.getElementById('oneispPackagePerformanceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($packageBreakdown->pluck('package_name')),
                datasets: [{
                    data: @json($packageBreakdown->pluck('revenue')),
                    backgroundColor: ['#6366f1', '#8b5cf6', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#14b8a6'],
                    borderColor: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `{{ $currency }} ${ctx.parsed.toLocaleString()}`,
                        },
                    },
                },
            },
        });
        oneispCharts.push(oneispPackagePerformanceChart);
        @endif

        function updateOneispChartTheme(isDark) {
            const gridColor = isDark ? '#374151' : '#f3f4f6';
            const textColor = isDark ? '#9ca3af' : '#6b7280';
            oneispCharts.forEach((chart) => {
                if (chart.options.scales?.y) {
                    if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
                    chart.options.scales.y.ticks = { ...(chart.options.scales.y.ticks || {}), color: textColor };
                }
                if (chart.options.scales?.x?.ticks) {
                    chart.options.scales.x.ticks.color = textColor;
                }
                if (chart.config.type === 'doughnut') {
                    chart.data.datasets[0].borderColor = isDark ? '#111827' : '#ffffff';
                }
                chart.update();
            });
        }

        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                const isDark = Alpine.$data(document.querySelector('[x-data]')).darkMode;
                updateOneispChartTheme(isDark);
            });
        });
    })();
</script>

<style>
    [x-cloak] { display: none !important; }

    @keyframes rp-rise {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .rp-rise {
        animation: rp-rise 500ms cubic-bezier(0.16, 1, 0.3, 1) both;
        animation-delay: var(--rp-delay, 0ms);
    }
    @media (prefers-reduced-motion: reduce) {
        .rp-rise { animation: none; }
    }
</style>

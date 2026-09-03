{{-- Self-contained JS for the "One ISP" dashboard layout. Expects $chartLabels, $chartData,
     $growthLabels, $growthData, $subscriptionsSparkline, $packageBreakdown, $currency in scope. --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const isDarkNow = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

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
                        borderRadius: 12,
                        borderSkipped: false,
                        barPercentage: 0.9,
                        categoryPercentage: 0.5,
                    },
                    {
                        // Always 0 today (no expense tracking yet). When that lands, this
                        // dataset should carry negative values so it floats below the zero
                        // line instead of stacking on top of Earning (see the JS-side .map()
                        // negation in fetchRevenueRange() below for the same reasoning).
                        label: 'Expense',
                        data: @json(array_fill(0, count($chartData ?? []), 0)),
                        backgroundColor: '#f97316',
                        borderRadius: 12,
                        borderSkipped: false,
                        barPercentage: 0.9,
                        categoryPercentage: 0.5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { drawBorder: false }, border: { display: false }, ticks: { font: { weight: '600' } } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { font: { weight: '600' } } },
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

        // Revenue Report's time-range filter — fetches fresh labels/data from
        // DashboardController::revenueChartData() and swaps them into the existing chart in
        // place, no page reload. Runs once immediately on load too, since the chart's initial
        // server-rendered data is a 6-month window that doesn't match any one dropdown option
        // (that window still backs the New Customers chart alongside it, so it's left alone
        // there) — this brings Revenue Report in sync with whatever the dropdown shows first.
        const oneispRevenueRangeSelect = document.getElementById('oneispRevenueRange');
        if (oneispRevenueRangeSelect) {
            function fetchRevenueRange(range) {
                fetch(`{{ route('dashboard.revenue-chart') }}?range=${range}`, { headers: { Accept: 'application/json' } })
                    .then((r) => r.json())
                    .then((json) => {
                        oneispRevenueChart.data.labels = json.labels;
                        oneispRevenueChart.data.datasets[0].data = json.earning;
                        oneispRevenueChart.data.datasets[1].data = json.expense.map((v) => -Math.abs(v));
                        oneispRevenueChart.update();
                    });
            }

            oneispRevenueRangeSelect.addEventListener('change', () => fetchRevenueRange(oneispRevenueRangeSelect.value));
            fetchRevenueRange(oneispRevenueRangeSelect.value);
        }

        @if($packageBreakdown->isNotEmpty())
        const oneispPackagePerformanceChart = new Chart(document.getElementById('oneispPackagePerformanceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($packageBreakdown->pluck('package_name')),
                datasets: [{
                    data: @json($packageBreakdown->pluck('revenue')),
                    backgroundColor: ['#6366f1', '#8b5cf6', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#14b8a6'],
                    borderColor: isDarkNow() ? '#111827' : '#ffffff',
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

        document.addEventListener('rp:theme-changed', (e) => updateOneispChartTheme(e.detail.dark));
    })();
</script>

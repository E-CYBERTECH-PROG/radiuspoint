{{-- Shared by all 3 dashboard layouts — the JS/CSS is identical regardless of arrangement, only
     the HTML layout differs between dashboard/{standard,ops,analytics}.blade.php. Expects
     $currentTimezone, $currency, $chartLabels, $chartData, $growthLabels, $growthData,
     $topPackages in scope. --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Live clock in the tenant's own configured timezone (Company Settings), not the
    // viewing device's local time — an admin checking the dashboard while traveling
    // should still see the network's actual local time, seconds included.
    function rpClock() {
        return {
            dateText: '',
            timeText: '',
            tick() {
                const now = new Date();
                this.dateText = new Intl.DateTimeFormat('en-GB', {
                    weekday: 'long', day: '2-digit', month: 'short', year: 'numeric',
                    timeZone: '{{ $currentTimezone ?? config("app.timezone") }}',
                }).format(now);
                this.timeText = new Intl.DateTimeFormat('en-GB', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
                    timeZone: '{{ $currentTimezone ?? config("app.timezone") }}',
                }).format(now);
                setTimeout(() => this.tick(), 1000);
            },
        };
    }

    // One component, one poller: the online counter used to poll every 5s on its own
    // timer while transactions/routers/M-Pesa polled every 10s on a second timer — two
    // concurrent intervals hitting the server independently. Merged into a single 10s
    // poll against one snapshot endpoint, halving the request rate this page generates.
    function dashboard(initial) {
        return {
            onlineCount: initial.online_now,
            onlineHidden: localStorage.getItem('rp_hide_online_count') === '1',
            moneyHidden: localStorage.getItem('rp_hide_money') === '1',
            deltas: [],
            deltaId: 0,
            pulse: '',
            recentTransactions: initial.recent_transactions,
            routerList: initial.routers,
            mpesaStatus: initial.mpesa_status,
            hotspotPanelUrlTemplate: "{{ route('hotspot-users.panel', ['hotspot_user' => '__ID__']) }}",

            openUserPanel(hotspotUserId) {
                window.dispatchEvent(new CustomEvent('open-user-panel', {
                    detail: { panelUrl: this.hotspotPanelUrlTemplate.replace('__ID__', hotspotUserId) },
                }));
            },

            toggleOnlineHidden() {
                this.onlineHidden = !this.onlineHidden;
                localStorage.setItem('rp_hide_online_count', this.onlineHidden ? '1' : '0');
            },

            toggleMoney() {
                this.moneyHidden = !this.moneyHidden;
                localStorage.setItem('rp_hide_money', this.moneyHidden ? '1' : '0');
            },

            startPolling() {
                setInterval(() => this.poll(), 10000);
            },

            async poll() {
                try {
                    const res = await fetch("{{ route('dashboard.live-snapshot') }}", { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    const diff = data.online_now - this.onlineCount;
                    if (diff !== 0) {
                        const id = this.deltaId++;
                        this.deltas.push({ id, value: diff });
                        setTimeout(() => { this.deltas = this.deltas.filter(d => d.id !== id); }, 900);

                        this.onlineCount = data.online_now;
                        this.pulse = diff > 0 ? 'rp-pulse-up' : 'rp-pulse-down';
                        setTimeout(() => { this.pulse = ''; }, 350);
                    }

                    this.recentTransactions = data.recent_transactions;
                    this.routerList = data.routers;
                    this.mpesaStatus = data.mpesa_status;
                } catch (e) {
                    // Transient failure — next tick retries, widgets just stay stale meanwhile.
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
                label: 'M-Pesa ({{ $currency }})',
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

    @if(array_sum($growthData) > 0)
    let growthChart = new Chart(document.getElementById('growthChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($growthLabels),
            datasets: [{
                label: 'New Customers',
                data: @json($growthData),
                backgroundColor: '#059669',
                borderRadius: 4,
                barPercentage: 0.4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { drawBorder: false }, border: { display: false } },
                x: { grid: { display: false }, border: { display: false } },
            },
        },
    });
    @endif

    @if($topPackages->isNotEmpty())
    let topPackagesChart = new Chart(document.getElementById('topPackagesChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($topPackages->pluck('package_name')),
            datasets: [{
                label: 'Purchases',
                data: @json($topPackages->pluck('purchase_count')),
                backgroundColor: '#d97706',
                borderRadius: 4,
                barPercentage: 0.5,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { display: false }, border: { display: false } },
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { drawBorder: false }, border: { display: false } },
            },
        },
    });
    @endif

    function updateChartTheme(isDark) {
        const gridColor = isDark ? '#1f2937' : '#f3f4f6';
        const textColor = isDark ? '#9ca3af' : '#6b7280';

        [revenueChart, typeof growthChart !== 'undefined' ? growthChart : null, typeof topPackagesChart !== 'undefined' ? topPackagesChart : null]
            .filter(Boolean)
            .forEach((chart) => {
                chart.options.scales.y.grid.color = gridColor;
                chart.options.scales.y.ticks.color = textColor;
                chart.options.scales.x.ticks.color = textColor;
                chart.update();
            });
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

    /* Staggered entrance on page load — each card fades/slides in slightly after the
       last (via each element's own --rp-delay custom property), a small but common
       "premium SaaS" polish detail rather than everything popping in at once. */
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

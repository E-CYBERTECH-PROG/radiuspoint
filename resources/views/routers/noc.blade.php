<x-sidebar-layout title="Fleet Status">
    @php
        $counts = ['active' => 0, 'offline' => 0, 'other' => 0];
        foreach ($routers as $r) {
            if ($r->status === 'active') $counts['active']++;
            elseif ($r->status === 'offline') $counts['offline']++;
            else $counts['other']++;
        }
    @endphp

    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Fleet Status</h1>
            <p class="text-muted mb-0">Every router at a glance &mdash; updated every minute automatically.</p>
        </div>
        <a href="{{ route('routers.index') }}" class="btn">
            <i class="ti ti-list icon"></i> Table View
        </a>
    </div>

    <div class="row row-cols-3 g-3 mb-4">
        <div class="col">
            <div class="card card-sm text-center">
                <div class="card-body">
                    <p class="fs-2 font-monospace fw-bold text-success mb-0">{{ $counts['active'] }}</p>
                    <p class="text-muted text-uppercase mb-0" style="font-size:.625rem">Online</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm text-center">
                <div class="card-body">
                    <p class="fs-2 font-monospace fw-bold text-danger mb-0">{{ $counts['offline'] }}</p>
                    <p class="text-muted text-uppercase mb-0" style="font-size:.625rem">Offline</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm text-center">
                <div class="card-body">
                    <p class="fs-2 font-monospace fw-bold text-warning mb-0">{{ $counts['other'] }}</p>
                    <p class="text-muted text-uppercase mb-0" style="font-size:.625rem">Awaiting Uplink</p>
                </div>
            </div>
        </div>
    </div>

    @if($routers->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-radar icon icon-lg mb-2 d-block"></i>
                <p class="text-uppercase small mb-0">No hardware deployed yet.</p>
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
            @foreach($routers as $router)
                <div class="col">
                    <div class="card card-sm h-100" data-rp-router-card data-rp-test-url="{{ route('routers.test-connection', $router) }}">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <x-router-board
                                        :port-count="$models[$router->board_model]['ports'] ?? 5"
                                        :sfp-count="$models[$router->board_model]['sfp'] ?? 0"
                                        :image="$models[$router->board_model]['image'] ?? null"
                                        :status="$router->status"
                                        compact
                                    />
                                    <div>
                                        <a href="{{ route('routers.show', $router) }}" class="fw-bold d-block">{{ $router->name }}</a>
                                        <span class="text-muted font-monospace" style="font-size:.625rem">{{ $router->ip_address }}</span>
                                    </div>
                                </div>
                                @if($router->status === 'active')
                                    <span class="badge bg-green-lt flex-shrink-0">Online</span>
                                @elseif($router->status === 'offline')
                                    <span class="badge bg-red-lt flex-shrink-0">Offline</span>
                                @else
                                    <span class="badge bg-amber-lt flex-shrink-0"><i class="ti ti-loader-2 icon-spin"></i> {{ ucfirst($router->status) }}</span>
                                @endif
                            </div>

                            <div class="text-muted small mb-3">
                                Last seen: {{ $router->last_seen ? $router->last_seen->diffForHumans() : 'never' }}
                            </div>

                            <div class="row g-2 small border-top pt-3 mb-3" data-rp-router-live style="display:none">
                                <div class="col-6">
                                    <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">CPU</p>
                                    <p class="fw-bold mb-0" data-rp-router-cpu>—</p>
                                </div>
                                <div class="col-6">
                                    <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Uptime</p>
                                    <p class="fw-bold mb-0" data-rp-router-uptime>—</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <button type="button" class="btn btn-sm flex-fill" data-rp-router-refresh>
                                    <i class="ti ti-refresh"></i> Refresh Now
                                </button>
                                <a href="{{ route('routers.monitor', $router) }}" class="text-muted" title="Live Monitor">
                                    <i class="ti ti-activity"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            document.querySelectorAll('[data-rp-router-card]').forEach(function (card) {
                var btn = card.querySelector('[data-rp-router-refresh]');
                var icon = btn.querySelector('i');
                var liveRow = card.querySelector('[data-rp-router-live]');
                var cpuEl = card.querySelector('[data-rp-router-cpu]');
                var uptimeEl = card.querySelector('[data-rp-router-uptime]');

                btn.addEventListener('click', async function () {
                    btn.disabled = true;
                    icon.className = 'ti ti-loader-2 icon-spin';

                    try {
                        var res = await fetch(card.getAttribute('data-rp-test-url'), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        });
                        var live = await res.json();
                        cpuEl.textContent = (live.cpu_load ?? '—') + '%';
                        uptimeEl.textContent = live.uptime ?? '—';
                        liveRow.style.display = '';
                    } catch (e) {
                        // Leave the card as-is — the status badge already reflects DB state.
                    } finally {
                        btn.disabled = false;
                        icon.className = 'ti ti-refresh';
                    }
                });
            });
        });
    </script>
</x-sidebar-layout>

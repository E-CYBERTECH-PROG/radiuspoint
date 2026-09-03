<x-sidebar-layout title="Routers">
    <div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Hardware &amp; Routers</h1>
            <p class="text-muted mb-0">Live monitoring and management of all deployed Mikrotik routers.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('routers.noc') }}" class="btn">
                <i class="ti ti-layout-grid icon"></i> <span class="d-none d-sm-inline">Fleet Status</span>
            </a>
        </div>
    </div>

    {{-- === STAT TILES === --}}
    @php
        $oneispRouterStatTiles = [
            ['label' => 'Total Hardware', 'value' => $stats['total'], 'icon' => 'ti-router', 'bg' => 'bg-primary-lt'],
            ['label' => 'Online', 'value' => $stats['online'], 'icon' => 'ti-plug-connected', 'bg' => 'bg-green-lt'],
            ['label' => 'Awaiting Uplink', 'value' => $stats['awaiting'], 'icon' => 'ti-loader-2', 'bg' => 'bg-amber-lt'],
            ['label' => 'Offline', 'value' => $stats['offline'], 'icon' => 'ti-plug-connected-x', 'bg' => 'bg-red-lt'],
        ];
    @endphp
    <div class="card mb-3" style="border-radius:.5rem">
        <div class="d-flex flex-column flex-sm-row rp-stat-strip">
            @foreach($oneispRouterStatTiles as $tile)
                <div class="flex-fill p-3">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fs-2 font-monospace fw-bold mb-0">{{ number_format($tile['value']) }}</p>
                            <p class="text-muted small mt-1 mb-0">{{ $tile['label'] }}</p>
                        </div>
                        <span class="avatar {{ $tile['bg'] }} flex-shrink-0"><i class="ti {{ $tile['icon'] }} fs-3"></i></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- === TOOLBAR + FILTERS + TABLE (one card) === --}}
    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-routers" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or VPN IP…" class="form-control">
                    </div>
                    <a href="{{ route('routers.create') }}" class="btn btn-primary">
                        <i class="ti ti-link icon"></i> <span class="d-none d-sm-inline">Deploy New Hardware</span>
                    </a>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Board</th>
                        <th>Hardware Identity</th>
                        <th>VPN IP (Uplink)</th>
                        <th>Access</th>
                        <th class="text-center">Connection Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routers as $router)
                        <tr>
                            <td>
                                <a href="{{ route('routers.show', $router) }}">
                                    <x-router-board
                                        :port-count="$models[$router->board_model]['ports'] ?? 5"
                                        :sfp-count="$models[$router->board_model]['sfp'] ?? 0"
                                        :image="$models[$router->board_model]['image'] ?? null"
                                        :status="$router->status"
                                        compact
                                    />
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold"><a href="{{ route('routers.show', $router) }}">{{ $router->name }}</a></div>
                                <span class="text-muted text-uppercase" style="font-size:.625rem">{{ $models[$router->board_model]['label'] ?? 'Unknown Model' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-primary-lt font-monospace">
                                    {{ $router->ip_address }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($router->web_proxy_port)
                                        <a href="http://{{ config('vpn.public_ip') }}:{{ $router->web_proxy_port }}" target="_blank" class="text-muted" title="Open Web Access">
                                            <i class="ti ti-world"></i>
                                        </a>
                                    @endif
                                    @if($router->winbox_proxy_port)
                                        <button type="button" class="text-muted" style="background:none;border:0" data-rp-copy="{{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }}" title="Copy Winbox Address">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('routers.monitor', $router) }}" class="text-muted" title="Live Monitor">
                                        <i class="ti ti-activity"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($router->status === 'active')
                                    <x-status-badge color="green" plain>Online</x-status-badge>
                                @elseif($router->status === 'provisioning' || $router->status === 'pending')
                                    <x-status-badge color="amber" icon="ti-loader-2 icon-spin">Awaiting Uplink</x-status-badge>
                                @else
                                    <x-status-badge color="orange" plain>Offline</x-status-badge>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($router->status === 'pending' || $router->status === 'provisioning')
                                    <a href="{{ route('routers.provision', $router->id) }}" class="text-warning fw-bold text-uppercase small d-inline-flex align-items-center gap-1">
                                        Resume Setup <i class="ti ti-arrow-right"></i>
                                    </a>
                                @else
                                    <div class="d-flex align-items-center justify-content-end gap-3">
                                        <a href="{{ route('routers.show', $router) }}" class="text-muted" title="Router Settings">
                                            <i class="ti ti-settings"></i>
                                        </a>
                                        <button type="button" title="Test Connection" class="text-muted" style="background:none;border:0" data-rp-test-connection="{{ route('routers.test-connection', $router) }}">
                                            <i class="ti ti-broadcast"></i>
                                        </button>
                                        <a href="{{ route('routers.show', $router) }}#decommission" class="text-muted" title="Remove Hardware (requires a confirmation code)">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-radar fs-1"></i></span>
                                @if(request()->hasAny(['search', 'status']))
                                    <p class="text-uppercase text-muted small mb-0">No hardware matches these filters.</p>
                                @else
                                    <p class="text-uppercase text-muted small mb-3">No hardware detected in the network topology.</p>
                                    <a href="{{ route('routers.create') }}" class="btn btn-primary">
                                        <i class="ti ti-link icon"></i> Deploy New Hardware
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <x-filter-modal name="routers" :clear-url="route('routers.index')">
            <div class="col-12">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['active', 'pending', 'provisioning', 'offline'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $routers->links() }}</div>

    <x-slot name="scripts">
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                document.addEventListener('click', function (e) {
                    var copyBtn = e.target.closest('[data-rp-copy]');
                    if (copyBtn) {
                        navigator.clipboard.writeText(copyBtn.getAttribute('data-rp-copy'));
                        var icon = copyBtn.querySelector('i');
                        icon.className = 'ti ti-check text-success';
                        setTimeout(function () { icon.className = 'ti ti-copy'; }, 2000);
                        return;
                    }

                    var testBtn = e.target.closest('[data-rp-test-connection]');
                    if (testBtn) {
                        var icon = testBtn.querySelector('i');
                        icon.className = 'ti ti-loader-2 icon-spin';
                        fetch(testBtn.getAttribute('data-rp-test-connection'), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                icon.className = data.status === 'online' ? 'ti ti-check text-success' : 'ti ti-x text-danger';
                            })
                            .catch(function () {
                                icon.className = 'ti ti-x text-danger';
                            })
                            .finally(function () {
                                setTimeout(function () { icon.className = 'ti ti-broadcast'; }, 3000);
                            });
                    }
                });
            });
        </script>
    </x-slot>
</x-sidebar-layout>

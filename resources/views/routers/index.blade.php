<x-sidebar-layout title="Routers">
    <div class="mb-2 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
        <div>
            <h2 class="mb-0">Routerboards</h2>
            <p class="text-muted small mb-0">Your deployed Mikrotik hardware.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('routers.noc') }}" class="btn btn-sm">
                <i class="ti ti-layout-grid icon"></i> <span class="d-none d-sm-inline">Fleet Status</span>
            </a>
        </div>
    </div>

    {{-- === TOOLBAR + FILTERS + TABLE (one card) === --}}
    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 bg-body-secondary border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                </div>
                <a href="{{ route('routers.create') }}" class="btn btn-primary">
                    <i class="ti ti-link icon"></i> <span class="d-none d-sm-inline">Deploy New Hardware</span>
                </a>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter table-sm table-hover">
                <thead>
                    <tr>
                        <th>Router</th>
                        <th>Status</th>
                        <th class="d-none d-md-table-cell">VPN IP (Uplink)</th>
                        <th class="d-none d-sm-table-cell">Access</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routers as $router)
                        <tr>
                            <td>
                                <a href="{{ route('routers.show', $router) }}" class="text-body fw-bold">
                                    {{ $router->name }}
                                </a>
                            </td>
                            <td>
                                @if($router->status === 'active')
                                    <x-status-badge color="green" dot>Online</x-status-badge>
                                @elseif($router->status === 'provisioning' || $router->status === 'pending')
                                    <x-status-badge color="amber" dot pulse>Awaiting Uplink</x-status-badge>
                                @else
                                    <x-status-badge color="orange" dot>Offline</x-status-badge>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="badge bg-primary-lt font-monospace">
                                    {{ $router->ip_address }}
                                </span>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                <div class="d-flex align-items-center gap-2">
                                    @if($router->winbox_proxy_port)
                                        <button type="button" class="text-muted" style="background:none;border:0" data-rp-copy="{{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }}" title="Copy Winbox Address">
                                            <i class="ti ti-device-desktop"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('routers.show', $router) }}#captive-portal" class="text-muted" title="Customize Captive Portal">
                                        <i class="ti ti-brush"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="text-end">
                                @if($router->status === 'pending' || $router->status === 'provisioning')
                                    <a href="{{ route('routers.provision', $router->id) }}" class="text-warning fw-bold text-uppercase small text-nowrap d-inline-flex align-items-center gap-1">
                                        <span class="d-none d-sm-inline">Resume Setup</span> <i class="ti ti-arrow-right"></i>
                                    </a>
                                @else
                                    <div class="d-flex align-items-center justify-content-end gap-2 gap-sm-3">
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
                            <td colspan="5" class="text-center py-5">
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
    </form>

    <div class="mt-3">{{ $routers->links('vendor.pagination.rp-circles') }}</div>

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
                        setTimeout(function () { icon.className = 'ti ti-device-desktop'; }, 2000);
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

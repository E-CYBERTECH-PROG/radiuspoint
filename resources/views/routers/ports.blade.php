<x-sidebar-layout title="Port Mapping">
    @php
        // Same "valid interface" filter as the old table, just precomputed once so the stat
        // tiles and the grid below both read from one collection instead of the grid computing
        // a $validInterfacesFound flag as a side effect of rendering.
        $validInterfaces = collect($interfaces)
            ->filter(fn ($i) => in_array($i['type'] ?? 'ether', ['ether', 'wlan', 'bridge', 'vlan']))
            ->values();

        $portConfig = $router->port_configuration ?? [];
        $roleOf = fn ($name) => $portConfig[$name]['role'] ?? 'none';

        $activeCount = $validInterfaces->filter(fn ($i) => ($i['running'] ?? 'false') === 'true')->count();
        $hotspotCount = $validInterfaces->filter(fn ($i) => in_array($roleOf($i['name']), ['hotspot', 'both']))->count();
        $pppoeCount = $validInterfaces->filter(fn ($i) => in_array($roleOf($i['name']), ['pppoe', 'both']))->count();

        $portStatTiles = [
            ['label' => 'Total Ports', 'value' => $validInterfaces->count(), 'icon' => 'ti-plug', 'bg' => 'bg-primary-lt'],
            ['label' => 'Link Active', 'value' => $activeCount, 'icon' => 'ti-plug-connected', 'bg' => 'bg-green-lt'],
            ['label' => 'Hotspot Mapped', 'value' => $hotspotCount, 'icon' => 'ti-wifi', 'bg' => 'bg-azure-lt'],
            ['label' => 'PPPoE Mapped', 'value' => $pppoeCount, 'icon' => 'ti-plug-connected-x', 'bg' => 'bg-purple-lt'],
        ];

        // Left-edge accent per role so the grid can be scanned for role at a glance without
        // reading every toggle — matches the icon tint used for Hotspot/PPPoE elsewhere below.
        $roleAccent = fn ($role) => match ($role) {
            'hotspot' => 'var(--tblr-azure)',
            'pppoe' => 'var(--tblr-purple)',
            'both' => 'var(--tblr-pink)',
            default => 'var(--tblr-border-color)',
        };
    @endphp

    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <x-status-badge color="green" dot pulse>Connected</x-status-badge>
                <span class="text-muted font-monospace" style="font-size:.625rem">{{ $router->ip_address }}</span>
            </div>
            <h1 class="mb-1">Map Ports</h1>
            <p class="text-muted mb-0">Assign a service to each physical port on {{ $router->name }}.</p>
        </div>
    </div>

    <div class="card mb-3" style="border-radius:.5rem">
        <div class="d-flex flex-column flex-sm-row rp-stat-strip">
            @foreach($portStatTiles as $tile)
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

    <form action="{{ route('routers.save-ports', $router->id) }}" method="POST">
        @csrf

        {{-- Tracks every shown interface so savePorts() can explicitly set unchecked ones to
             "none" — kept as one batch here rather than interleaved inside <tbody>, where a
             bare <input> isn't valid between <tr> elements. --}}
        @foreach($validInterfaces as $interface)
            <input type="hidden" name="all_interfaces[]" value="{{ $interface['name'] }}">
        @endforeach

        @if($validInterfaces->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="ti ti-alert-triangle icon icon-lg text-warning mb-2 d-block"></i>
                    <p class="text-uppercase small mb-0">No compatible Ethernet or WLAN interfaces detected on target hardware.</p>
                </div>
            </div>
        @else
            <div class="d-flex justify-content-end mb-3">
                <div class="input-icon" style="max-width:16rem">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" id="rp-port-search" class="form-control form-control-sm" placeholder="Filter by port name...">
                </div>
            </div>

            <div class="card mb-3">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Port</th>
                                <th class="text-center" style="width:1%">Hotspot</th>
                                <th class="text-center" style="width:1%">PPPoE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($validInterfaces as $interface)
                                @php $existingRole = $roleOf($interface['name']); @endphp

                                <tr data-rp-port-row data-rp-port-search="{{ strtolower($interface['name']) }}">
                                    <td data-rp-port-accent style="box-shadow:inset 3px 0 0 0 {{ $roleAccent($existingRole) }};transition:box-shadow .2s ease">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm {{ $interface['type'] == 'wlan' ? 'bg-azure-lt' : 'bg-primary-lt' }} flex-shrink-0">
                                                <i class="ti {{ $interface['type'] == 'wlan' ? 'ti-wifi' : 'ti-plug' }}"></i>
                                            </span>
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold">{{ $interface['name'] }}</span>
                                                    @if(isset($interface['running']) && $interface['running'] == 'true')
                                                        <x-status-badge color="green" dot title="Port is Active/Running">Up</x-status-badge>
                                                    @else
                                                        <x-status-badge color="gray" dot title="Port is Inactive">Down</x-status-badge>
                                                    @endif
                                                    {{-- A port with neither box checked is left exactly as-is — not disabled, not
                                                         reassigned, free for its normal DHCP/manual use. This just makes that
                                                         explicit rather than leaving it to look unfinished. --}}
                                                    <span class="badge bg-secondary-lt" data-rp-port-dynamic @if(in_array($existingRole, ['hotspot', 'both', 'pppoe'])) style="display:none" @endif>Dynamic</span>
                                                </div>
                                                @if(isset($interface['mac-address']))
                                                    <span class="text-muted font-monospace" style="font-size:.625rem">{{ $interface['mac-address'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <label class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                            <input type="checkbox" name="hotspot_ports[]" value="{{ $interface['name'] }}" class="form-check-input" data-rp-port-toggle @checked(in_array($existingRole, ['hotspot', 'both']))>
                                            <span class="visually-hidden">Hotspot on {{ $interface['name'] }}</span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                            <input type="checkbox" name="pppoe_ports[]" value="{{ $interface['name'] }}" class="form-check-input" data-rp-port-toggle @checked(in_array($existingRole, ['pppoe', 'both']))>
                                            <span class="visually-hidden">PPPoE on {{ $interface['name'] }}</span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small px-3 py-2 border-top mb-0"><i class="ti ti-info-circle"></i> Check both boxes on a port to run Hotspot and PPPoE together on the same interface.</p>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 p-4 bg-blue-lt rounded">
                <div class="d-flex align-items-start gap-2">
                    <i class="ti ti-lock icon flex-shrink-0 mt-1"></i>
                    <div>
                        <p class="text-uppercase small fw-bold mb-1">Before you continue</p>
                        <p class="text-muted small mb-0">This configures the live hardware and finalizes the deployment.</p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary flex-shrink-0">
                    <i class="ti ti-lock icon"></i> Lock Config &amp; Deploy
                </button>
            </div>
        @endif
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var roleColors = {
                none: 'var(--tblr-border-color)',
                hotspot: 'var(--tblr-azure)',
                pppoe: 'var(--tblr-purple)',
                both: 'var(--tblr-pink)',
            };

            document.querySelectorAll('[data-rp-port-row]').forEach(function (row) {
                var toggles = row.querySelectorAll('[data-rp-port-toggle]');
                var badge = row.querySelector('[data-rp-port-dynamic]');
                var accentCell = row.querySelector('[data-rp-port-accent]');
                var hotspotToggle = row.querySelector('[name="hotspot_ports[]"]');
                var pppoeToggle = row.querySelector('[name="pppoe_ports[]"]');

                function sync() {
                    var role = hotspotToggle.checked && pppoeToggle.checked
                        ? 'both'
                        : hotspotToggle.checked ? 'hotspot' : pppoeToggle.checked ? 'pppoe' : 'none';

                    badge.style.display = role === 'none' ? '' : 'none';
                    accentCell.style.boxShadow = 'inset 3px 0 0 0 ' + roleColors[role];
                }

                toggles.forEach(function (t) { t.addEventListener('change', sync); });
            });

            // Client-side filter — large switches can have 24+ ports, so narrowing by name
            // matters more here than on a plain scroll.
            var searchInput = document.getElementById('rp-port-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    var q = searchInput.value.trim().toLowerCase();
                    document.querySelectorAll('[data-rp-port-search]').forEach(function (row) {
                        row.style.display = row.getAttribute('data-rp-port-search').includes(q) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</x-sidebar-layout>

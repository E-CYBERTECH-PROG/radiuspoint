@php
    // Server-side tab definitions (route name, RouterOS field => column label) — keeps every
    // tab pane's table generated from one shared config instead of ten near-identical blocks.
    $tabs = [
        'logs' => ['label' => 'Logs', 'icon' => 'ti-file-text', 'route' => 'routers.monitor.logs', 'columns' => [
            'time' => 'Time', 'topics' => 'Topics', 'message' => 'Message',
        ]],
        'ethernet' => ['label' => 'Ethernet', 'icon' => 'ti-plug', 'route' => 'routers.monitor.interfaces', 'actions' => true, 'columns' => [
            'name' => 'Name', 'running' => 'Running', 'comment' => 'Comment', 'disabled' => 'Disabled',
        ]],
        'hotspot' => ['label' => 'Online Hotspot', 'icon' => 'ti-wifi', 'route' => 'routers.monitor.hotspot-active', 'actions' => true, 'columns' => [
            'user' => 'Username', 'address' => 'Address', 'mac-address' => 'MAC', 'uptime' => 'Uptime', 'session-time-left' => 'Time Left',
        ]],
        'pppoe' => ['label' => 'Online PPPoE', 'icon' => 'ti-plug-connected', 'route' => 'routers.monitor.pppoe-active', 'actions' => true, 'columns' => [
            'name' => 'Username', 'address' => 'Address', 'caller-id' => 'Caller ID', 'uptime' => 'Uptime',
        ]],
        'addresses' => ['label' => 'Addresses', 'icon' => 'ti-map-pin', 'route' => 'routers.monitor.addresses', 'columns' => [
            'address' => 'Address', 'network' => 'Network', 'interface' => 'Interface',
        ]],
        'neighbors' => ['label' => 'Neighbors', 'icon' => 'ti-radar', 'route' => 'routers.monitor.neighbors', 'columns' => [
            'identity' => 'Identity', 'address' => 'Address', 'interface' => 'Interface', 'platform' => 'Platform',
        ]],
        'dhcp' => ['label' => 'DHCP Leases', 'icon' => 'ti-list', 'route' => 'routers.monitor.dhcp-leases', 'columns' => [
            'address' => 'Address', 'mac-address' => 'MAC', 'host-name' => 'Hostname', 'server' => 'Server', 'status' => 'Status',
        ]],
        'wireless' => ['label' => 'Wireless Clients', 'icon' => 'ti-antenna', 'route' => 'routers.monitor.wireless-clients', 'columns' => [
            'interface' => 'Interface', 'mac-address' => 'MAC', 'signal-strength' => 'Signal', 'uptime' => 'Uptime',
        ]],
        'health' => ['label' => 'System Health', 'icon' => 'ti-heart', 'route' => 'routers.monitor.health', 'columns' => [
            'name' => 'Sensor', 'value' => 'Value', 'type' => 'Type',
        ]],
        'queues' => ['label' => 'Simple Queues', 'icon' => 'ti-adjustments', 'route' => 'routers.monitor.queues', 'columns' => [
            'name' => 'Name', 'target' => 'Target', 'max-limit' => 'Max Limit',
        ]],
        'firewall' => ['label' => 'Firewall Rules', 'icon' => 'ti-shield', 'route' => 'routers.monitor.firewall-rules', 'columns' => [
            'chain' => 'Chain', 'action' => 'Action', 'comment' => 'Comment', 'bytes' => 'Bytes', 'packets' => 'Packets',
        ]],
    ];
@endphp
<x-sidebar-layout title="Live Monitor — {{ $router->name }}">
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <a href="{{ route('routers.show', $router) }}" class="d-inline-flex align-items-center gap-2">
                <i class="ti ti-arrow-left icon"></i> Back to {{ $router->name }}
            </a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="rp-monitor-dot" class="rounded-circle bg-secondary" style="width:.625rem;height:.625rem"></span>
            <h1 class="mb-0">Live Monitor</h1>
        </div>
        <p class="text-muted mt-1 mb-0" id="rp-monitor-subtitle">Connecting...</p>
    </div>

    {{-- Action feedback toast: every remote-control button (reboot, toggle, disconnect,
         block) reports through this one place rather than each having its own inline state. --}}
    <div class="alert d-none align-items-center gap-2" id="rp-action-message-wrap">
        <i class="icon flex-shrink-0" id="rp-action-message-icon"></i>
        <span id="rp-action-message-text"></span>
    </div>

    {{-- Overview: reuses the same resource/uptime/CPU/memory data as the router detail page --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">System Resource</h3>
                <button type="button" id="rp-reboot-btn" class="btn btn-outline-danger btn-sm">
                    <i class="ti ti-power" id="rp-reboot-icon"></i>
                    <span id="rp-reboot-label">Reboot Router</span>
                </button>
            </div>

            <p class="text-muted" id="rp-overview-loading">Checking connection...</p>

            <div class="d-none flex-column flex-md-row gap-4" id="rp-overview-info">
                <div class="row g-3 small flex-fill">
                    <div class="col-4">
                        <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Board</p>
                        <p class="fw-bold mb-0" id="rp-overview-board">—</p>
                    </div>
                    <div class="col-4">
                        <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">RouterOS Version</p>
                        <p class="fw-bold mb-0" id="rp-overview-version">—</p>
                    </div>
                    <div class="col-4">
                        <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Uptime</p>
                        <p class="fw-bold mb-0" id="rp-overview-uptime">—</p>
                    </div>
                </div>
                <div class="d-flex align-items-start justify-content-center gap-4 flex-shrink-0">
                    <x-gauge-ring id="rp-overview-gauge-cpu" color="var(--tblr-primary)" label="CPU Load" icon="ti-cpu" :size="88" />
                    <x-gauge-ring id="rp-overview-gauge-mem" color="var(--tblr-success)" label="Memory Used" icon="ti-database" :size="88" detail="—" />
                </div>
            </div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="mb-3 d-flex flex-wrap gap-2" id="rp-monitor-tabs">
        <button type="button" class="btn btn-sm btn-primary" data-rp-tab="traffic">
            <i class="ti ti-chart-line"></i> Traffic Monitor
        </button>
        <button type="button" class="btn btn-sm" data-rp-tab="resource">
            <i class="ti ti-cpu"></i> CPU / Memory History
        </button>
        <button type="button" class="btn btn-sm" data-rp-tab="torch">
            <i class="ti ti-target-arrow"></i> Top Talkers
        </button>
        <button type="button" class="btn btn-sm" data-rp-tab="console">
            <i class="ti ti-terminal-2"></i> Console
        </button>
        @foreach($tabs as $key => $tab)
            <button type="button" class="btn btn-sm" data-rp-tab="{{ $key }}">
                <i class="ti {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Traffic Monitor pane: not part of the generic table loop below, since it needs an
         interface selector + live chart rather than a static table. --}}
    <div class="card mb-3" data-rp-pane="traffic">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-uppercase text-muted small fw-bold mb-0">Interface</label>
                    <select id="rp-traffic-select" class="form-select form-select-sm w-auto"></select>
                </div>
                <div class="d-flex align-items-center gap-4">
                    <div class="text-end">
                        <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Download</p>
                        <p class="fw-bold text-primary fs-4 font-monospace mb-0" id="rp-traffic-rx">0 bps</p>
                    </div>
                    <div class="text-end">
                        <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Upload</p>
                        <p class="fw-bold text-success fs-4 font-monospace mb-0" id="rp-traffic-tx">0 bps</p>
                    </div>
                </div>
            </div>
            {{-- The canvas stays mounted and visible at a stable size at all times, error or
                 not — hiding it on every failed poll collapses it to 0×0, and Chart.js's own
                 layout code doesn't tolerate laying out against a 0-size canvas: confirmed
                 live, that threw inside Chart.js's internal update path, which landed back in
                 this same catch block and re-hid the canvas — a self-sustaining crash loop
                 that never let the graph recover on its own. --}}
            <p class="text-center text-danger text-uppercase small py-2 d-none" id="rp-traffic-error"></p>
            <div style="height: 320px">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>

    {{-- CPU / Memory History: same rolling-chart pattern as Traffic Monitor, polling
         /system/resource/print instead of interface counters. --}}
    <div class="card mb-3 d-none" data-rp-pane="resource">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-end gap-4 mb-4">
                <div class="text-end">
                    <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">CPU Load</p>
                    <p class="fw-bold text-primary fs-4 font-monospace mb-0" id="rp-resource-cpu">—</p>
                </div>
                <div class="text-end">
                    <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Memory Free</p>
                    <p class="fw-bold text-success fs-4 font-monospace mb-0" id="rp-resource-mem">—</p>
                </div>
            </div>
            <p class="text-center text-danger text-uppercase small py-2 d-none" id="rp-resource-error"></p>
            <div style="height: 320px">
                <canvas id="resourceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Talkers: /tool/torch blocks for its own duration= (2s), so this is a manual
         "Scan Now" button rather than an auto-polled chart. --}}
    <div class="card mb-3 d-none" data-rp-pane="torch">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-uppercase text-muted small fw-bold mb-0">Interface</label>
                    <select id="rp-torch-select" class="form-select form-select-sm w-auto"></select>
                </div>
                <button type="button" id="rp-torch-scan-btn" class="btn btn-primary btn-sm">
                    <i class="ti ti-scan" id="rp-torch-scan-icon"></i>
                    <span id="rp-torch-scan-label">Scan Now</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Protocol</th>
                            <th>TX</th>
                            <th>RX</th>
                        </tr>
                    </thead>
                    <tbody id="rp-torch-rows">
                        <tr><td colspan="5" class="text-center text-muted py-5">
                            <i class="ti ti-target-arrow icon icon-lg mb-2 d-block"></i>
                            <p class="text-uppercase small mb-0">Run a scan to see live top talkers.</p>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Console: raw RouterOS API commands, one per submission. Executes directly against
         live hardware — every attempt is logged server-side regardless of outcome. --}}
    <div class="card mb-3 d-none" data-rp-pane="console">
        <div class="card-body">
            <div class="alert alert-warning d-flex align-items-center gap-2 py-2">
                <i class="ti ti-alert-triangle"></i> Executes directly against live hardware. No undo. Every command is logged.
            </div>

            <form id="rp-terminal-form" class="d-flex align-items-center gap-2 mb-3">
                <span class="font-monospace text-muted">&gt;</span>
                <input type="text" id="rp-terminal-input" placeholder="/interface/print  or  /ip/address/add address=10.0.0.5/24 interface=ether1" class="form-control font-monospace" autocomplete="off">
                <button type="submit" class="btn btn-primary" id="rp-terminal-run-btn">
                    <i class="ti ti-player-play" id="rp-terminal-run-icon"></i> Run
                </button>
            </form>

            <div class="bg-dark text-light font-monospace small rounded p-3 mb-3" style="max-height:320px;overflow-y:auto" id="rp-terminal-scrollback">
                <p class="text-muted mb-0" id="rp-terminal-output-empty">No commands run yet this session.</p>
            </div>

            <div>
                <p class="text-uppercase text-muted small mb-2">Recent History</p>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Command</th>
                                <th>By</th>
                                <th>When</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody id="rp-terminal-history">
                            <tr><td colspan="4" class="text-center text-muted">No history yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab panes: one shared table shape per tab, columns/route driven server-side above --}}
    @foreach($tabs as $key => $tab)
        <div class="card d-none" data-rp-pane="{{ $key }}">
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            @foreach($tab['columns'] as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                            @if($tab['actions'] ?? false)
                                <th class="text-end">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    @php $colspan = count($tab['columns']) + ($tab['actions'] ?? false ? 1 : 0); @endphp
                    <tbody id="rp-tab-body-{{ $key }}" data-rp-colspan="{{ $colspan }}">
                        <tr><td colspan="{{ $colspan }}" class="text-center text-muted py-5">
                            <i class="ti ti-loader-2 icon-spin icon icon-lg mb-2 d-block"></i>
                            <p class="text-uppercase small mb-0">Loading...</p>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <x-slot name="scripts">
        {{-- Pinned, not "latest" — an unpinned Chart.js URL silently picks up new releases in
             production with no code change on our side to review. --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                var router = { id: {{ $router->id }}, name: @json($router->name), ip: @json($router->ip_address) };

                // Server-driven tab metadata (columns + which action buttons each row gets).
                var tabsMeta = @json($tabs);

                var endpoints = {
                    logs: "{{ route('routers.monitor.logs', $router) }}",
                    ethernet: "{{ route('routers.monitor.interfaces', $router) }}",
                    hotspot: "{{ route('routers.monitor.hotspot-active', $router) }}",
                    pppoe: "{{ route('routers.monitor.pppoe-active', $router) }}",
                    addresses: "{{ route('routers.monitor.addresses', $router) }}",
                    neighbors: "{{ route('routers.monitor.neighbors', $router) }}",
                    dhcp: "{{ route('routers.monitor.dhcp-leases', $router) }}",
                    wireless: "{{ route('routers.monitor.wireless-clients', $router) }}",
                    health: "{{ route('routers.monitor.health', $router) }}",
                    queues: "{{ route('routers.monitor.queues', $router) }}",
                    firewall: "{{ route('routers.monitor.firewall-rules', $router) }}",
                    interfaceList: "{{ route('routers.monitor.interface-list', $router) }}",
                    traffic: "{{ route('routers.monitor.traffic', $router) }}",
                    resource: "{{ route('routers.monitor.resource', $router) }}",
                    torch: "{{ route('routers.monitor.torch', $router) }}",
                    testConnection: "{{ route('routers.test-connection', $router) }}",
                    terminalExecute: "{{ route('routers.terminal.execute', $router) }}",
                    terminalHistory: "{{ route('routers.terminal.history', $router) }}",
                    reboot: "{{ route('routers.actions.reboot', $router) }}",
                    toggleInterface: "{{ route('routers.actions.toggle-interface', $router) }}",
                    disconnectHotspot: "{{ route('routers.actions.disconnect-hotspot', $router) }}",
                    disconnectPppoe: "{{ route('routers.actions.disconnect-pppoe', $router) }}",
                    blockHotspot: "{{ route('routers.actions.block-hotspot', $router) }}",
                };

                // Kept as plain closure variables, not reactive state — a Chart.js instance's
                // internal state (scales, layout boxes, canvas context) is large and partly
                // circular, so it stays outside anything that gets diffed/serialized.
                var trafficChart = null;
                var resourceChart = null;

                var state = {
                    activeTab: 'traffic',
                    tabData: {},
                    tabError: {},
                    tabLoading: {},
                    trafficInterfaces: [],
                    trafficSelected: null,
                    trafficPolling: null,
                    torchSelected: null,
                    torchScanning: false,
                    resourcePolling: null,
                    terminalHistoryLoaded: false,
                    actionBusy: false,
                    rebooting: false,
                };
                Object.keys(tabsMeta).forEach(function (k) { state.tabData[k] = null; state.tabError[k] = null; state.tabLoading[k] = false; });

                function formatBits(bps) {
                    bps = Number(bps) || 0;
                    if (bps >= 1e9) return (bps / 1e9).toFixed(2) + ' Gbps';
                    if (bps >= 1e6) return (bps / 1e6).toFixed(2) + ' Mbps';
                    if (bps >= 1e3) return (bps / 1e3).toFixed(1) + ' Kbps';
                    return bps + ' bps';
                }

                function esc(v) {
                    var div = document.createElement('div');
                    div.textContent = v == null ? '' : String(v);
                    return div.innerHTML;
                }

                // === Overview ===
                function setGaugeArc(id, percent) {
                    var arc = document.getElementById(id + '-arc');
                    if (!arc) return;
                    var circumference = parseFloat(arc.dataset.circumference);
                    var clamped = Math.max(0, Math.min(100, percent));
                    arc.style.strokeDashoffset = circumference - (clamped / 100) * circumference;
                }

                async function loadOverview() {
                    try {
                        var res = await fetch(endpoints.testConnection, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        });
                        var data = await res.json();
                        var online = data.status === 'online';

                        document.getElementById('rp-overview-board').textContent = data.board_model_detected ?? '—';
                        document.getElementById('rp-overview-version').textContent = data.version ?? '—';
                        document.getElementById('rp-overview-uptime').textContent = data.uptime ?? '—';

                        var cpuLoad = data.cpu_load ?? null;
                        setGaugeArc('rp-overview-gauge-cpu', cpuLoad ?? 0);
                        document.getElementById('rp-overview-gauge-cpu-value').textContent = cpuLoad !== null ? cpuLoad + '%' : '—';

                        var freeMemory = data.free_memory ?? null;
                        var totalMemory = data.total_memory ?? null;
                        var memPercent = freeMemory && totalMemory ? Math.round(((totalMemory - freeMemory) / totalMemory) * 100) : 0;
                        setGaugeArc('rp-overview-gauge-mem', memPercent);
                        document.getElementById('rp-overview-gauge-mem-value').textContent = freeMemory && totalMemory ? memPercent + '%' : '—';
                        document.getElementById('rp-overview-gauge-mem-detail').textContent = freeMemory && totalMemory
                            ? Math.round(freeMemory / 1048576) + ' MB / ' + Math.round(totalMemory / 1048576) + ' MB'
                            : '—';

                        document.getElementById('rp-overview-loading').style.display = 'none';
                        var infoEl = document.getElementById('rp-overview-info');
                        infoEl.classList.remove('d-none');
                        infoEl.classList.add('d-flex');

                        document.getElementById('rp-monitor-dot').className = 'rounded-circle ' + (online ? 'bg-success' : 'bg-secondary');
                        document.getElementById('rp-monitor-subtitle').textContent = online
                            ? 'Connected to MikroTik — ' + (data.identity || router.ip)
                            : 'Connecting...';
                    } catch (e) {
                        document.getElementById('rp-monitor-dot').className = 'rounded-circle bg-secondary';
                        document.getElementById('rp-monitor-subtitle').textContent = 'Connecting...';
                    }
                }

                // === Tab switching ===
                function selectTab(tab) {
                    if (state.activeTab === 'traffic' && tab !== 'traffic') stopTrafficPolling();
                    if (state.activeTab === 'resource' && tab !== 'resource') stopResourcePolling();

                    state.activeTab = tab;

                    document.querySelectorAll('[data-rp-tab]').forEach(function (btn) {
                        btn.className = 'btn btn-sm ' + (btn.getAttribute('data-rp-tab') === tab ? 'btn-primary' : '');
                    });
                    document.querySelectorAll('[data-rp-pane]').forEach(function (pane) {
                        pane.classList.toggle('d-none', pane.getAttribute('data-rp-pane') !== tab);
                    });

                    if (tab === 'traffic') {
                        initTraffic();
                    } else if (tab === 'resource') {
                        initResource();
                    } else if (tab === 'torch') {
                        initTorch();
                    } else if (tab === 'console') {
                        initTerminal();
                    } else if (state.tabData[tab] === null && !state.tabLoading[tab]) {
                        loadTab(tab);
                    }
                }

                document.querySelectorAll('[data-rp-tab]').forEach(function (btn) {
                    btn.addEventListener('click', function () { selectTab(btn.getAttribute('data-rp-tab')); });
                });

                // === Generic tab tables ===
                function renderGenericTab(key) {
                    var meta = tabsMeta[key];
                    var body = document.getElementById('rp-tab-body-' + key);
                    var colspan = body.getAttribute('data-rp-colspan');

                    if (state.tabLoading[key] && state.tabData[key] === null) {
                        body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-muted py-5"><i class="ti ti-loader-2 icon-spin icon icon-lg mb-2 d-block"></i><p class="text-uppercase small mb-0">Loading...</p></td></tr>';
                        return;
                    }
                    if (state.tabError[key]) {
                        body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-danger py-5"><i class="ti ti-alert-circle icon icon-lg mb-2 d-block"></i><p class="text-uppercase small mb-0">' + esc(state.tabError[key]) + '</p></td></tr>';
                        return;
                    }
                    var rows = state.tabData[key] || [];
                    if (rows.length === 0) {
                        body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-muted py-5"><i class="ti ti-inbox icon icon-lg mb-2 d-block"></i><p class="text-uppercase small mb-0">Nothing here right now.</p></td></tr>';
                        return;
                    }

                    var fields = Object.keys(meta.columns);
                    body.innerHTML = rows.map(function (row, i) {
                        var cells = fields.map(function (f) {
                            return '<td class="font-monospace small text-truncate" style="max-width:20rem">' + esc(row[f] ?? '—') + '</td>';
                        }).join('');

                        var actions = '';
                        if (key === 'ethernet') {
                            var disabled = row['disabled'] === 'true';
                            actions = '<td class="text-end"><button type="button" class="btn btn-sm ' + (disabled ? 'btn-outline-success' : 'btn-outline-warning') + '" data-rp-row-action="toggle-interface" data-rp-row-index="' + i + '">' + (disabled ? 'Enable' : 'Disable') + '</button></td>';
                        } else if (key === 'hotspot') {
                            actions = '<td class="text-end text-nowrap">' +
                                '<button type="button" class="text-muted me-3" style="background:none;border:0" title="Disconnect" data-rp-row-action="disconnect-hotspot" data-rp-row-index="' + i + '"><i class="ti ti-plug-connected-x"></i></button>' +
                                '<button type="button" class="text-muted" style="background:none;border:0" title="Block" data-rp-row-action="block-hotspot" data-rp-row-index="' + i + '"><i class="ti ti-ban"></i></button>' +
                                '</td>';
                        } else if (key === 'pppoe') {
                            actions = '<td class="text-end"><button type="button" class="text-muted" style="background:none;border:0" title="Disconnect" data-rp-row-action="disconnect-pppoe" data-rp-row-index="' + i + '"><i class="ti ti-plug-connected-x"></i></button></td>';
                        }

                        return '<tr>' + cells + actions + '</tr>';
                    }).join('');

                    body._rpRows = rows;
                }

                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-rp-row-action]');
                    if (!btn) return;
                    var body = btn.closest('tbody');
                    var row = body._rpRows[parseInt(btn.getAttribute('data-rp-row-index'), 10)];
                    var action = btn.getAttribute('data-rp-row-action');

                    if (action === 'toggle-interface') toggleInterface(row);
                    else if (action === 'disconnect-hotspot') disconnectHotspotUser(row);
                    else if (action === 'block-hotspot') blockHotspotUser(row);
                    else if (action === 'disconnect-pppoe') disconnectPppoeUser(row);
                });

                async function loadTab(tab) {
                    state.tabLoading[tab] = true;
                    state.tabError[tab] = null;
                    renderGenericTab(tab);
                    try {
                        var res = await fetch(endpoints[tab], { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        if (json.error) {
                            state.tabError[tab] = json.error;
                            state.tabData[tab] = [];
                        } else {
                            state.tabData[tab] = json.data;
                        }
                    } catch (e) {
                        state.tabError[tab] = 'Failed to fetch — router may be unreachable.';
                        state.tabData[tab] = [];
                    }
                    state.tabLoading[tab] = false;
                    renderGenericTab(tab);
                }

                // === Traffic Monitor ===
                async function initTraffic() {
                    if (!trafficChart) {
                        await loadTrafficInterfaces();
                        buildTrafficChart();
                        startTrafficPolling();
                    } else {
                        startTrafficPolling();
                    }
                }

                async function loadTrafficInterfaces() {
                    var select = document.getElementById('rp-traffic-select');
                    try {
                        var res = await fetch(endpoints.interfaceList, { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        if (json.error) {
                            showTrafficError(json.error);
                            return;
                        }
                        // Loopback can't carry meaningful bandwidth — drop it from the picker.
                        state.trafficInterfaces = (json.data || []).filter(function (i) { return i.type !== 'loopback'; });
                        var running = state.trafficInterfaces.find(function (i) { return i.running === 'true'; });
                        state.trafficSelected = (running || state.trafficInterfaces[0] || {}).name || null;

                        select.innerHTML = state.trafficInterfaces.map(function (iface) {
                            return '<option value="' + esc(iface.name) + '">' + esc(iface.name) + (iface.running === 'true' ? ' (up)' : ' (down)') + '</option>';
                        }).join('');
                        select.value = state.trafficSelected;

                        var torchSelect = document.getElementById('rp-torch-select');
                        torchSelect.innerHTML = state.trafficInterfaces.map(function (iface) {
                            return '<option value="' + esc(iface.name) + '">' + esc(iface.name) + '</option>';
                        }).join('');
                    } catch (e) {
                        showTrafficError('Could not load interface list.');
                    }
                }

                function showTrafficError(message) {
                    var el = document.getElementById('rp-traffic-error');
                    if (message) {
                        el.textContent = message;
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                }

                function buildTrafficChart() {
                    var canvas = document.getElementById('trafficChart');
                    if (!canvas) return;
                    // Destroy any chart already bound to this canvas first — Chart.js throws
                    // "Canvas is already in use" rather than replacing it, and this can run
                    // more than once for the same canvas.
                    var existing = Chart.getChart(canvas);
                    if (existing) existing.destroy();
                    trafficChart = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: [],
                            datasets: [
                                { label: 'Download', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.3, fill: true, pointRadius: 0, borderWidth: 2 },
                                { label: 'Upload', data: [], borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', tension: 0.3, fill: true, pointRadius: 0, borderWidth: 2 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: { intersect: false, mode: 'index' },
                            scales: {
                                y: { beginAtZero: true, ticks: { callback: function (v) { return formatBits(v); } } },
                                x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
                            },
                            plugins: { legend: { position: 'top' } },
                        },
                    });
                }

                function startTrafficPolling() {
                    stopTrafficPolling();
                    pollTraffic();
                    // 2s balances "feels live" against round-trip latency without hammering the router.
                    state.trafficPolling = setInterval(pollTraffic, 2000);
                }

                function stopTrafficPolling() {
                    if (state.trafficPolling) {
                        clearInterval(state.trafficPolling);
                        state.trafficPolling = null;
                    }
                }

                async function pollTraffic() {
                    if (!state.trafficSelected || !trafficChart) return;
                    try {
                        var res = await fetch(endpoints.traffic + '?interface=' + encodeURIComponent(state.trafficSelected), { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        if (json.error) {
                            showTrafficError(json.error);
                            return;
                        }
                        showTrafficError(null);
                        var row = (json.data && json.data[0]) || {};
                        var rxNow = parseInt(row['rx-bits-per-second'] || 0);
                        var txNow = parseInt(row['tx-bits-per-second'] || 0);
                        document.getElementById('rp-traffic-rx').textContent = formatBits(rxNow);
                        document.getElementById('rp-traffic-tx').textContent = formatBits(txNow);

                        trafficChart.data.labels.push(new Date().toLocaleTimeString());
                        trafficChart.data.datasets[0].data.push(rxNow);
                        trafficChart.data.datasets[1].data.push(txNow);
                        // Keep a rolling one-minute window (30 points @ 2s) — enough to see a
                        // trend without the chart growing unbounded on a page left open.
                        if (trafficChart.data.labels.length > 30) {
                            trafficChart.data.labels.shift();
                            trafficChart.data.datasets[0].data.shift();
                            trafficChart.data.datasets[1].data.shift();
                        }
                        trafficChart.update('none');
                    } catch (e) {
                        showTrafficError('Failed to fetch — router may be unreachable.');
                    }
                }

                document.getElementById('rp-traffic-select').addEventListener('change', function (e) {
                    state.trafficSelected = e.target.value;
                    // Clear history on interface switch so the graph doesn't show a
                    // discontinuous jump between two unrelated interfaces' traffic levels.
                    if (trafficChart) {
                        trafficChart.data.labels = [];
                        trafficChart.data.datasets[0].data = [];
                        trafficChart.data.datasets[1].data = [];
                        trafficChart.update('none');
                    }
                    showTrafficError(null);
                });

                // === CPU / Memory History ===
                async function initResource() {
                    if (!resourceChart) {
                        buildResourceChart();
                        startResourcePolling();
                    } else {
                        startResourcePolling();
                    }
                }

                function showResourceError(message) {
                    var el = document.getElementById('rp-resource-error');
                    if (message) {
                        el.textContent = message;
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                }

                function buildResourceChart() {
                    var canvas = document.getElementById('resourceChart');
                    if (!canvas) return;
                    var existing = Chart.getChart(canvas);
                    if (existing) existing.destroy();
                    resourceChart = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: [],
                            datasets: [
                                { label: 'CPU %', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.3, fill: true, pointRadius: 0, borderWidth: 2, yAxisID: 'y' },
                                { label: 'Memory Free (MB)', data: [], borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', tension: 0.3, fill: true, pointRadius: 0, borderWidth: 2, yAxisID: 'y1' },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: { intersect: false, mode: 'index' },
                            scales: {
                                y: { beginAtZero: true, max: 100, position: 'left', title: { display: true, text: 'CPU %' } },
                                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'MB free' } },
                                x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
                            },
                            plugins: { legend: { position: 'top' } },
                        },
                    });
                }

                function startResourcePolling() {
                    stopResourcePolling();
                    pollResource();
                    state.resourcePolling = setInterval(pollResource, 2000);
                }

                function stopResourcePolling() {
                    if (state.resourcePolling) {
                        clearInterval(state.resourcePolling);
                        state.resourcePolling = null;
                    }
                }

                async function pollResource() {
                    if (!resourceChart) return;
                    try {
                        var res = await fetch(endpoints.resource, { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        if (json.error) {
                            showResourceError(json.error);
                            return;
                        }
                        showResourceError(null);
                        var row = (json.data && json.data[0]) || {};
                        var cpuNow = parseInt(row['cpu-load'] || 0);
                        var memNow = parseInt(row['free-memory'] || 0);
                        document.getElementById('rp-resource-cpu').textContent = cpuNow + '%';
                        document.getElementById('rp-resource-mem').textContent = Math.round(memNow / 1048576) + ' MB';

                        resourceChart.data.labels.push(new Date().toLocaleTimeString());
                        resourceChart.data.datasets[0].data.push(cpuNow);
                        resourceChart.data.datasets[1].data.push(Math.round(memNow / 1048576));
                        if (resourceChart.data.labels.length > 30) {
                            resourceChart.data.labels.shift();
                            resourceChart.data.datasets[0].data.shift();
                            resourceChart.data.datasets[1].data.shift();
                        }
                        resourceChart.update('none');
                    } catch (e) {
                        showResourceError('Failed to fetch — router may be unreachable.');
                    }
                }

                // === Top Talkers ===
                async function initTorch() {
                    if (state.trafficInterfaces.length === 0) {
                        await loadTrafficInterfaces();
                    }
                    var torchSelect = document.getElementById('rp-torch-select');
                    if (!state.torchSelected) {
                        state.torchSelected = state.trafficSelected;
                        torchSelect.value = state.torchSelected;
                    }
                }

                document.getElementById('rp-torch-select').addEventListener('change', function (e) {
                    state.torchSelected = e.target.value;
                });

                document.getElementById('rp-torch-scan-btn').addEventListener('click', async function () {
                    if (!state.torchSelected || state.torchScanning) return;
                    state.torchScanning = true;
                    var btn = document.getElementById('rp-torch-scan-btn');
                    var icon = document.getElementById('rp-torch-scan-icon');
                    var label = document.getElementById('rp-torch-scan-label');
                    btn.disabled = true;
                    icon.className = 'ti ti-loader-2 icon-spin';
                    label.textContent = 'Scanning (2s)...';

                    var rowsEl = document.getElementById('rp-torch-rows');

                    try {
                        var res = await fetch(endpoints.torch + '?interface=' + encodeURIComponent(state.torchSelected), { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        if (json.error) {
                            rowsEl.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-5"><i class="ti ti-alert-circle icon icon-lg mb-2 d-block"></i><p class="text-uppercase small mb-0">' + esc(json.error) + '</p></td></tr>';
                        } else {
                            var rows = json.data || [];
                            if (rows.length === 0) {
                                rowsEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-target-arrow icon icon-lg mb-2 d-block"></i><p class="text-uppercase small mb-0">No traffic detected during the scan window.</p></td></tr>';
                            } else {
                                rowsEl.innerHTML = rows.map(function (row) {
                                    return '<tr>' +
                                        '<td class="font-monospace small">' + esc((row['src-address'] || '—') + (row['src-port'] ? ':' + row['src-port'] : '')) + '</td>' +
                                        '<td class="font-monospace small">' + esc((row['dst-address'] || '—') + (row['dst-port'] ? ':' + row['dst-port'] : '')) + '</td>' +
                                        '<td class="font-monospace small">' + esc(row['ip-protocol'] || '—') + '</td>' +
                                        '<td class="font-monospace small">' + formatBits((row['tx'] || 0) * 8 / 2) + '</td>' +
                                        '<td class="font-monospace small">' + formatBits((row['rx'] || 0) * 8 / 2) + '</td>' +
                                        '</tr>';
                                }).join('');
                            }
                        }
                    } catch (e) {
                        rowsEl.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-5"><i class="ti ti-alert-circle icon icon-lg mb-2 d-block"></i><p class="text-uppercase small mb-0">Failed to fetch — router may be unreachable.</p></td></tr>';
                    }

                    state.torchScanning = false;
                    btn.disabled = false;
                    icon.className = 'ti ti-scan';
                    label.textContent = 'Scan Now';
                });

                // === Console ===
                function renderTerminalHistory(history) {
                    var body = document.getElementById('rp-terminal-history');
                    if (!history || history.length === 0) {
                        body.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No history yet.</td></tr>';
                        return;
                    }
                    body.innerHTML = history.map(function (log) {
                        return '<tr>' +
                            '<td class="font-monospace">' + esc(log.command) + '</td>' +
                            '<td class="text-muted">' + esc(log.user) + '</td>' +
                            '<td class="text-muted">' + esc(log.at) + '</td>' +
                            '<td><i class="ti ' + (log.success ? 'ti-check text-success' : 'ti-x text-danger') + '"></i></td>' +
                            '</tr>';
                    }).join('');
                }

                async function initTerminal() {
                    if (state.terminalHistoryLoaded) return;
                    state.terminalHistoryLoaded = true;
                    try {
                        var res = await fetch(endpoints.terminalHistory, { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        renderTerminalHistory(json.data || []);
                    } catch (e) {
                        // History is a nice-to-have — leave it empty rather than blocking the console.
                    }
                }

                document.getElementById('rp-terminal-form').addEventListener('submit', async function (e) {
                    e.preventDefault();
                    var input = document.getElementById('rp-terminal-input');
                    var command = input.value.trim();
                    if (!command) return;
                    if (/\b(remove|reset|reboot)\b/i.test(command) && !(await window.rpConfirmAsync('Run this against live hardware?\n\n' + command))) {
                        return;
                    }

                    var runBtn = document.getElementById('rp-terminal-run-btn');
                    var runIcon = document.getElementById('rp-terminal-run-icon');
                    var scrollback = document.getElementById('rp-terminal-scrollback');
                    var emptyEl = document.getElementById('rp-terminal-output-empty');

                    runBtn.disabled = true;
                    input.disabled = true;
                    runIcon.className = 'ti ti-loader-2 icon-spin';

                    var entry;
                    try {
                        var res = await fetch(endpoints.terminalExecute, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                            body: JSON.stringify({ command: command }),
                        });
                        var json = await res.json();
                        entry = { command: command, success: !json.error, result: json.error || JSON.stringify(json.data, null, 2) };
                    } catch (e) {
                        entry = { command: command, success: false, result: 'Failed to reach the router.' };
                    }

                    if (emptyEl) emptyEl.remove();
                    var div = document.createElement('div');
                    div.className = 'mb-3';
                    var cmdP = document.createElement('p');
                    cmdP.className = 'text-info mb-1';
                    cmdP.textContent = '> ' + entry.command;
                    var pre = document.createElement('pre');
                    pre.className = 'text-wrap mb-0 ' + (entry.success ? '' : 'text-danger');
                    pre.textContent = entry.result;
                    div.appendChild(cmdP);
                    div.appendChild(pre);
                    scrollback.appendChild(div);
                    scrollback.scrollTop = scrollback.scrollHeight;

                    input.value = '';
                    runBtn.disabled = false;
                    input.disabled = false;
                    runIcon.className = 'ti ti-player-play';
                    state.terminalHistoryLoaded = false;
                    initTerminal();
                });

                // === Action toast + remote-control actions ===
                function showActionMessage(message, isError) {
                    var wrap = document.getElementById('rp-action-message-wrap');
                    var icon = document.getElementById('rp-action-message-icon');
                    var text = document.getElementById('rp-action-message-text');
                    wrap.className = 'alert d-flex align-items-center gap-2 ' + (isError ? 'alert-danger' : 'alert-success');
                    icon.className = 'icon flex-shrink-0 ti ' + (isError ? 'ti-alert-circle' : 'ti-circle-check');
                    text.textContent = message;
                    setTimeout(function () { wrap.classList.add('d-none'); }, 4000);
                }

                async function postAction(url, body) {
                    state.actionBusy = true;
                    try {
                        var res = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                            body: JSON.stringify(body || {}),
                        });
                        var json = await res.json();
                        if (json.error) {
                            showActionMessage(json.error, true);
                            return false;
                        }
                        showActionMessage(json.message || 'Done.', false);
                        return true;
                    } catch (e) {
                        showActionMessage('Failed to reach the router.', true);
                        return false;
                    } finally {
                        state.actionBusy = false;
                    }
                }

                document.getElementById('rp-reboot-btn').addEventListener('click', async function () {
                    if (!(await window.rpConfirmAsync('Reboot ' + router.name + '? It will be unreachable for about a minute.'))) return;
                    var icon = document.getElementById('rp-reboot-icon');
                    var label = document.getElementById('rp-reboot-label');
                    icon.className = 'ti ti-loader-2 icon-spin';
                    label.textContent = 'Rebooting...';
                    await postAction(endpoints.reboot);
                    icon.className = 'ti ti-power';
                    label.textContent = 'Reboot Router';
                });

                async function toggleInterface(row) {
                    var disable = row['disabled'] !== 'true';
                    if (disable && !(await window.rpConfirmAsync('Disable interface ' + row['name'] + '? Anything connected through it will drop.'))) return;
                    var ok = await postAction(endpoints.toggleInterface, { interface: row['name'], disabled: disable });
                    if (ok) loadTab('ethernet');
                }

                async function disconnectHotspotUser(row) {
                    if (!(await window.rpConfirmAsync('Disconnect ' + (row['user'] || row['address']) + '?'))) return;
                    var ok = await postAction(endpoints.disconnectHotspot, { id: row['.id'] });
                    if (ok) loadTab('hotspot');
                }

                async function disconnectPppoeUser(row) {
                    if (!(await window.rpConfirmAsync('Disconnect ' + (row['name'] || row['address']) + '?'))) return;
                    var ok = await postAction(endpoints.disconnectPppoe, { id: row['.id'] });
                    if (ok) loadTab('pppoe');
                }

                async function blockHotspotUser(row) {
                    if (!(await window.rpConfirmAsync('Block ' + row['address'] + ' (' + (row['user'] || 'unknown user') + ')? They will be denied access until unblocked in Winbox.'))) return;
                    var ok = await postAction(endpoints.blockHotspot, { address: row['address'] });
                    if (ok) loadTab('hotspot');
                }

                loadOverview();
                selectTab('traffic');
            })();
        </script>
    </x-slot>
</x-sidebar-layout>

@php
    // Server-side tab definitions (route name, RouterOS field => column label) — keeps every
    // tab pane's table generated from one shared Alpine loop instead of six near-identical blocks.
    $tabs = [
        'logs' => ['label' => 'Logs', 'icon' => 'bx-file', 'route' => 'routers.monitor.logs', 'columns' => [
            'time' => 'Time', 'topics' => 'Topics', 'message' => 'Message',
        ]],
        'ethernet' => ['label' => 'Ethernet', 'icon' => 'bx-ethernet', 'route' => 'routers.monitor.interfaces', 'actions' => true, 'columns' => [
            'name' => 'Name', 'running' => 'Running', 'comment' => 'Comment', 'disabled' => 'Disabled',
        ]],
        'hotspot' => ['label' => 'Online Hotspot', 'icon' => 'bx-wifi', 'route' => 'routers.monitor.hotspot-active', 'actions' => true, 'columns' => [
            'user' => 'Username', 'address' => 'Address', 'mac-address' => 'MAC', 'uptime' => 'Uptime', 'session-time-left' => 'Time Left',
        ]],
        'pppoe' => ['label' => 'Online PPPoE', 'icon' => 'bx-plug', 'route' => 'routers.monitor.pppoe-active', 'actions' => true, 'columns' => [
            'name' => 'Username', 'address' => 'Address', 'caller-id' => 'Caller ID', 'uptime' => 'Uptime',
        ]],
        'addresses' => ['label' => 'Addresses', 'icon' => 'bx-map-pin', 'route' => 'routers.monitor.addresses', 'columns' => [
            'address' => 'Address', 'network' => 'Network', 'interface' => 'Interface',
        ]],
        'neighbors' => ['label' => 'Neighbors', 'icon' => 'bx-radar', 'route' => 'routers.monitor.neighbors', 'columns' => [
            'identity' => 'Identity', 'address' => 'Address', 'interface' => 'Interface', 'platform' => 'Platform',
        ]],
        'dhcp' => ['label' => 'DHCP Leases', 'icon' => 'bx-list-ul', 'route' => 'routers.monitor.dhcp-leases', 'columns' => [
            'address' => 'Address', 'mac-address' => 'MAC', 'host-name' => 'Hostname', 'server' => 'Server', 'status' => 'Status',
        ]],
        'wireless' => ['label' => 'Wireless Clients', 'icon' => 'bx-wifi-2', 'route' => 'routers.monitor.wireless-clients', 'columns' => [
            'interface' => 'Interface', 'mac-address' => 'MAC', 'signal-strength' => 'Signal', 'uptime' => 'Uptime',
        ]],
        'health' => ['label' => 'System Health', 'icon' => 'bx-heart', 'route' => 'routers.monitor.health', 'columns' => [
            'name' => 'Sensor', 'value' => 'Value', 'type' => 'Type',
        ]],
        'queues' => ['label' => 'Simple Queues', 'icon' => 'bx-slider-alt', 'route' => 'routers.monitor.queues', 'columns' => [
            'name' => 'Name', 'target' => 'Target', 'max-limit' => 'Max Limit',
        ]],
        'firewall' => ['label' => 'Firewall Rules', 'icon' => 'bx-shield', 'route' => 'routers.monitor.firewall-rules', 'columns' => [
            'chain' => 'Chain', 'action' => 'Action', 'comment' => 'Comment', 'bytes' => 'Bytes', 'packets' => 'Packets',
        ]],
    ];
@endphp
<x-sidebar-layout title="Live Monitor — {{ $router->name }}">
    <div x-data="routerMonitor()" x-init="init()">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <a href="{{ route('routers.show', $router) }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2">
                    <i class="bx bx-left-arrow-alt text-lg"></i> Back to {{ $router->name }}
                </a>
                <a href="{{ route('routers.show', $router) }}#captive-portal" class="text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors inline-flex items-center gap-2">
                    <i class="bx bx-globe text-lg"></i> Captive Portal
                </a>
            </div>
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full" :class="loaded ? 'bg-green-500 animate-pulse' : 'bg-gray-300'"></span>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Live Monitor</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="loaded ? `Connected to MikroTik — ${identity || '{{ $router->ip_address }}'}` : 'Connecting...'"></p>
        </div>

        {{-- Action feedback toast: every remote-control button (reboot, toggle, disconnect,
             block) reports through this one place rather than each having its own inline state. --}}
        <div x-show="actionMessage" x-cloak x-transition
             class="mb-4 px-4 py-3 rounded-lg text-sm font-bold flex items-center gap-2"
             :class="actionMessageIsError ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-900/50' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-900/50'">
            <i class="bx" :class="actionMessageIsError ? 'bx-error-circle' : 'bx-check-circle'"></i>
            <span x-text="actionMessage"></span>
        </div>

        {{-- Overview: reuses the same resource/uptime/CPU/memory data as the router detail page --}}
        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border-0 shadow-[0_10px_24px_-20px_rgba(0,0,0,.32)] hover:shadow-[0_14px_28px_-20px_rgba(0,0,0,.4)] transition-shadow mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-md font-bold text-gray-900 dark:text-white">System Resource</h3>
                <button type="button" @click="rebootRouter()" :disabled="rebooting" class="inline-flex items-center gap-2 text-xs font-bold text-red-600 dark:text-red-400 hover:text-red-700 disabled:opacity-50 border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg transition-colors">
                    <i class="bx" :class="rebooting ? 'bx-loader-alt bx-spin' : 'bx-power-off'"></i>
                    <span x-text="rebooting ? 'Rebooting...' : 'Reboot Router'"></span>
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm" x-show="!loaded">
                <p class="text-gray-400 col-span-4">Checking connection...</p>
            </div>
            <div x-show="loaded" x-cloak class="flex flex-col md:flex-row gap-6">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm flex-1">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Board</p>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="boardDetected || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">RouterOS Version</p>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="version || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Uptime</p>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="uptime || '—'"></p>
                    </div>
                </div>
                <div class="flex items-start justify-center gap-6 shrink-0">
                    <x-gauge-ring percent-expr="cpuLoad" value-expr="cpuLoad !== null ? cpuLoad + '%' : '—'" color="#2563eb" label="CPU Load" :size="84" />
                    <x-gauge-ring percent-expr="memPercent" value-expr="memPercent !== null ? memPercent + '%' : '—'" detail-expr="memoryText" color="#16a34a" label="Memory Used" :size="84" />
                </div>
            </div>
        </div>

        {{-- Tab bar --}}
        <div class="mb-4 flex flex-wrap gap-2">
            <button @click="selectTab('traffic')" :class="activeTab === 'traffic' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                <i class='bx bx-line-chart'></i> Traffic Monitor
            </button>
            <button @click="selectTab('resource')" :class="activeTab === 'resource' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                <i class='bx bx-microchip'></i> CPU / Memory History
            </button>
            <button @click="selectTab('torch')" :class="activeTab === 'torch' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                <i class='bx bx-target-lock'></i> Top Talkers
            </button>
            <button @click="selectTab('console')" :class="activeTab === 'console' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                <i class='bx bx-terminal'></i> Console
            </button>
            @foreach($tabs as $key => $tab)
                <button @click="selectTab('{{ $key }}')" :class="activeTab === '{{ $key }}' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                    <i class='bx {{ $tab['icon'] }}'></i> {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        {{-- Traffic Monitor pane: not part of the generic table loop below, since it needs an
             interface selector + live chart rather than a static table. --}}
        <div x-show="activeTab === 'traffic'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Interface</label>
                    <select x-model="trafficSelected" @change="changeTrafficInterface()" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-sm font-bold py-2 px-3 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <template x-for="iface in trafficInterfaces" :key="iface.name">
                            <option :value="iface.name" x-text="iface.name + (iface.running === 'true' ? ' (up)' : ' (down)')"></option>
                        </template>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Download</p>
                        <p class="font-bold text-blue-600 dark:text-blue-400 text-lg font-fira" x-text="formatBits(rxNow)"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Upload</p>
                        <p class="font-bold text-green-600 dark:text-green-400 text-lg font-fira" x-text="formatBits(txNow)"></p>
                    </div>
                </div>
            </div>
            <template x-if="trafficError">
                <p class="text-center text-red-400 text-xs tracking-widest uppercase py-8" x-text="trafficError"></p>
            </template>
            <div x-show="!trafficError" style="height: 320px">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        {{-- CPU / Memory History: same rolling-chart pattern as Traffic Monitor, polling
             /system/resource/print instead of interface counters. --}}
        <div x-show="activeTab === 'resource'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 mb-6">
            <div class="flex items-center justify-end gap-6 mb-6">
                <div class="text-right">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">CPU Load</p>
                    <p class="font-bold text-blue-600 dark:text-blue-400 text-lg font-fira" x-text="(cpuNow ?? '—') + '%'"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Memory Free</p>
                    <p class="font-bold text-green-600 dark:text-green-400 text-lg font-fira" x-text="memNow !== null ? Math.round(memNow / 1048576) + ' MB' : '—'"></p>
                </div>
            </div>
            <template x-if="resourceError">
                <p class="text-center text-red-400 text-xs tracking-widest uppercase py-8" x-text="resourceError"></p>
            </template>
            <div x-show="!resourceError" style="height: 320px">
                <canvas id="resourceChart"></canvas>
            </div>
        </div>

        {{-- Top Talkers: /tool/torch blocks for its own duration= (2s), so this is a manual
             "Scan Now" button rather than an auto-polled chart. --}}
        <div x-show="activeTab === 'torch'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Interface</label>
                    <select x-model="torchSelected" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-sm font-bold py-2 px-3 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <template x-for="iface in trafficInterfaces" :key="iface.name">
                            <option :value="iface.name" x-text="iface.name"></option>
                        </template>
                    </select>
                </div>
                <button type="button" @click="scanTorch()" :disabled="torchScanning" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-sm py-2 px-4 rounded-lg transition-colors">
                    <i class="bx" :class="torchScanning ? 'bx-loader-alt bx-spin' : 'bx-scan'"></i>
                    <span x-text="torchScanning ? 'Scanning (2s)...' : 'Scan Now'"></span>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-3">Source</th>
                            <th class="px-6 py-3">Destination</th>
                            <th class="px-6 py-3">Protocol</th>
                            <th class="px-6 py-3">TX</th>
                            <th class="px-6 py-3">RX</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        <template x-if="torchError">
                            <tr><td colspan="5" class="px-6 py-12 text-center text-red-400">
                                <i class="bx bx-error-circle text-2xl mb-2"></i>
                                <p class="text-xs tracking-widest uppercase" x-text="torchError"></p>
                            </td></tr>
                        </template>
                        <template x-if="!torchError && torchRows.length === 0">
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-target-lock text-2xl mb-2"></i>
                                <p class="text-xs tracking-widest uppercase">Run a scan to see live top talkers.</p>
                            </td></tr>
                        </template>
                        <template x-for="(row, i) in torchRows" :key="i">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 font-fira text-xs" x-text="(row['src-address'] || '—') + (row['src-port'] ? ':' + row['src-port'] : '')"></td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 font-fira text-xs" x-text="(row['dst-address'] || '—') + (row['dst-port'] ? ':' + row['dst-port'] : '')"></td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 font-fira text-xs" x-text="row['ip-protocol'] || '—'"></td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 font-fira text-xs" x-text="formatBits((row['tx'] || 0) * 8 / 2)"></td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 font-fira text-xs" x-text="formatBits((row['rx'] || 0) * 8 / 2)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Console: raw RouterOS API commands, one per submission. Executes directly against
             live hardware — every attempt is logged server-side regardless of outcome. --}}
        <div x-show="activeTab === 'console'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 mb-6">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900/50 rounded-lg px-3 py-2 mb-4">
                <i class="bx bx-error"></i> Executes directly against live hardware. No undo. Every command is logged.
            </div>

            <form @submit.prevent="runTerminalCommand()" class="flex items-center gap-2 mb-4">
                <span class="font-fira text-gray-400 text-sm">&gt;</span>
                <input type="text" x-model="terminalInput" :disabled="terminalRunning"
                       placeholder="/interface/print  or  /ip/address/add address=10.0.0.5/24 interface=ether1"
                       class="flex-1 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white font-fira text-sm py-2 px-3 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" :disabled="terminalRunning || !terminalInput" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-sm py-2 px-4 rounded-lg transition-colors">
                    <i class="bx" :class="terminalRunning ? 'bx-loader-alt bx-spin' : 'bx-play'"></i> Run
                </button>
            </form>

            <div class="bg-gray-950 dark:bg-black rounded-lg p-4 font-fira text-xs text-gray-300 overflow-y-auto mb-4" style="max-height: 320px" x-ref="terminalScrollback">
                <template x-if="terminalOutput.length === 0">
                    <p class="text-gray-500">No commands run yet this session.</p>
                </template>
                <template x-for="(entry, i) in terminalOutput" :key="i">
                    <div class="mb-3">
                        <p class="text-blue-400">&gt; <span x-text="entry.command"></span></p>
                        <pre class="whitespace-pre-wrap break-all" :class="entry.success ? 'text-gray-300' : 'text-red-400'" x-text="entry.result"></pre>
                    </div>
                </template>
            </div>

            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-2">Recent History</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                                <th class="px-4 py-2">Command</th>
                                <th class="px-4 py-2">By</th>
                                <th class="px-4 py-2">When</th>
                                <th class="px-4 py-2">Result</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                            <template x-if="terminalHistory.length === 0">
                                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No history yet.</td></tr>
                            </template>
                            <template x-for="(log, i) in terminalHistory" :key="i">
                                <tr>
                                    <td class="px-4 py-2 font-fira text-gray-700 dark:text-gray-300" x-text="log.command"></td>
                                    <td class="px-4 py-2 text-gray-500">{{--  --}}<span x-text="log.user"></span></td>
                                    <td class="px-4 py-2 text-gray-400" x-text="log.at"></td>
                                    <td class="px-4 py-2">
                                        <i class="bx" :class="log.success ? 'bx-check text-green-500' : 'bx-x text-red-500'"></i>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab panes: one shared table shape per tab, columns/route driven server-side above --}}
        @foreach($tabs as $key => $tab)
            <div x-show="activeTab === '{{ $key }}'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                                @foreach($tab['columns'] as $label)
                                    <th class="px-6 py-3">{{ $label }}</th>
                                @endforeach
                                @if($tab['actions'] ?? false)
                                    <th class="px-6 py-3 text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        @php $colspan = count($tab['columns']) + ($tab['actions'] ?? false ? 1 : 0); @endphp
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                            <template x-if="tabLoading['{{ $key }}'] && tabData['{{ $key }}'] === null">
                                <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-loader-alt bx-spin text-2xl mb-2"></i>
                                    <p class="text-xs tracking-widest uppercase">Loading...</p>
                                </td></tr>
                            </template>
                            <template x-if="tabError['{{ $key }}']">
                                <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-red-400">
                                    <i class="bx bx-error-circle text-2xl mb-2"></i>
                                    <p class="text-xs tracking-widest uppercase" x-text="tabError['{{ $key }}']"></p>
                                </td></tr>
                            </template>
                            <template x-if="tabData['{{ $key }}'] !== null && !tabError['{{ $key }}'] && tabData['{{ $key }}'].length === 0">
                                <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-inbox text-2xl mb-2"></i>
                                    <p class="text-xs tracking-widest uppercase">Nothing here right now.</p>
                                </td></tr>
                            </template>
                            <template x-for="(row, i) in (tabData['{{ $key }}'] || [])" :key="i">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                    @foreach(array_keys($tab['columns']) as $field)
                                        <td class="px-6 py-3 text-gray-700 dark:text-gray-300 font-fira text-xs max-w-md truncate" x-text="row['{{ $field }}'] ?? '—'"></td>
                                    @endforeach
                                    @if($key === 'ethernet')
                                        <td class="px-6 py-3 text-right">
                                            <button type="button" @click="toggleInterface(row)" :disabled="actionBusy"
                                                    class="text-xs font-bold px-2.5 py-1 rounded-lg border transition-colors"
                                                    :class="row['disabled'] === 'true' ? 'text-green-600 border-green-200 hover:bg-green-50 dark:border-green-900/50 dark:hover:bg-green-900/20' : 'text-amber-600 border-amber-200 hover:bg-amber-50 dark:border-amber-900/50 dark:hover:bg-amber-900/20'">
                                                <span x-text="row['disabled'] === 'true' ? 'Enable' : 'Disable'"></span>
                                            </button>
                                        </td>
                                    @elseif($key === 'hotspot')
                                        <td class="px-6 py-3 text-right whitespace-nowrap">
                                            <button type="button" @click="disconnectHotspotUser(row)" :disabled="actionBusy" title="Disconnect" class="text-gray-400 hover:text-amber-600 transition-colors mr-3">
                                                <i class="bx bx-unlink text-lg"></i>
                                            </button>
                                            <button type="button" @click="blockHotspotUser(row)" :disabled="actionBusy" title="Block" class="text-gray-400 hover:text-red-600 transition-colors">
                                                <i class="bx bx-block text-lg"></i>
                                            </button>
                                        </td>
                                    @elseif($key === 'pppoe')
                                        <td class="px-6 py-3 text-right">
                                            <button type="button" @click="disconnectPppoeUser(row)" :disabled="actionBusy" title="Disconnect" class="text-gray-400 hover:text-amber-600 transition-colors">
                                                <i class="bx bx-unlink text-lg"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            function routerMonitor() {
                return {
                    activeTab: 'traffic',
                    loaded: false,
                    identity: null,
                    boardDetected: null,
                    version: null,
                    uptime: null,
                    cpuLoad: null,
                    freeMemory: null,
                    totalMemory: null,

                    // Generated from the same server-side $tabs array the tab buttons/panes loop
                    // over, instead of a hand-duplicated key list — the previous hardcoded list
                    // only covered 6 of the 11 real tabs (dhcp/wireless/health/queues/firewall
                    // were missing entirely), so opening any of those threw "Cannot read
                    // properties of undefined (reading 'length')" the instant Alpine evaluated
                    // that tab's x-if, confirmed via a real headless-browser render.
                    tabData: @json(array_fill_keys(array_keys($tabs), null)),
                    tabError: @json(array_fill_keys(array_keys($tabs), null)),
                    tabLoading: @json(array_fill_keys(array_keys($tabs), false)),

                    // Traffic Monitor state — polled on a timer rather than loaded once, since this
                    // tab shows a live-updating chart rather than a static table.
                    trafficInterfaces: [],
                    trafficSelected: null,
                    trafficChart: null,
                    trafficPolling: null,
                    trafficError: null,
                    rxNow: 0,
                    txNow: 0,

                    // CPU/Memory History state — same rolling-chart shape as Traffic Monitor.
                    resourceChart: null,
                    resourcePolling: null,
                    resourceError: null,
                    cpuNow: null,
                    memNow: null,

                    // Top Talkers state — manual scan, not polled (each scan blocks ~2s).
                    torchSelected: null,
                    torchScanning: false,
                    torchError: null,
                    torchRows: [],

                    // Console state
                    terminalInput: '',
                    terminalOutput: [],
                    terminalRunning: false,
                    terminalHistory: [],
                    terminalHistoryLoaded: false,

                    // Remote-control action state — shared across the reboot button and every
                    // per-row action button, so only one action can be in flight at a time and
                    // feedback always surfaces through the one toast at the top of the page.
                    rebooting: false,
                    actionBusy: false,
                    actionMessage: null,
                    actionMessageIsError: false,

                    endpoints: {
                        logs: "{{ route('routers.monitor.logs', $router) }}",
                        ethernet: "{{ route('routers.monitor.interfaces', $router) }}",
                        hotspot: "{{ route('routers.monitor.hotspot-active', $router) }}",
                        pppoe: "{{ route('routers.monitor.pppoe-active', $router) }}",
                        addresses: "{{ route('routers.monitor.addresses', $router) }}",
                        neighbors: "{{ route('routers.monitor.neighbors', $router) }}",
                        interfaceList: "{{ route('routers.monitor.interface-list', $router) }}",
                        traffic: "{{ route('routers.monitor.traffic', $router) }}",
                        resource: "{{ route('routers.monitor.resource', $router) }}",
                        torch: "{{ route('routers.monitor.torch', $router) }}",
                        terminalExecute: "{{ route('routers.terminal.execute', $router) }}",
                        terminalHistory: "{{ route('routers.terminal.history', $router) }}",
                        reboot: "{{ route('routers.actions.reboot', $router) }}",
                        toggleInterface: "{{ route('routers.actions.toggle-interface', $router) }}",
                        disconnectHotspot: "{{ route('routers.actions.disconnect-hotspot', $router) }}",
                        disconnectPppoe: "{{ route('routers.actions.disconnect-pppoe', $router) }}",
                        blockHotspot: "{{ route('routers.actions.block-hotspot', $router) }}",
                    },

                    get memoryText() {
                        if (!this.freeMemory || !this.totalMemory) return '—';
                        const freeMb = Math.round(this.freeMemory / 1048576);
                        const totalMb = Math.round(this.totalMemory / 1048576);
                        return `${freeMb} MB / ${totalMb} MB`;
                    },

                    get memPercent() {
                        if (!this.freeMemory || !this.totalMemory) return null;
                        return Math.round(((this.totalMemory - this.freeMemory) / this.totalMemory) * 100);
                    },

                    init() {
                        this.loadOverview();
                        this.selectTab('traffic');
                    },

                    async loadOverview() {
                        try {
                            const response = await fetch("{{ route('routers.test-connection', $router) }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                            });
                            const data = await response.json();
                            this.identity = data.identity ?? null;
                            this.boardDetected = data.board_model_detected ?? null;
                            this.version = data.version ?? null;
                            this.uptime = data.uptime ?? null;
                            this.cpuLoad = data.cpu_load ?? null;
                            this.freeMemory = data.free_memory ?? null;
                            this.totalMemory = data.total_memory ?? null;
                            this.loaded = data.status === 'online';
                        } catch (e) {
                            this.loaded = false;
                        }
                    },

                    selectTab(tab) {
                        // Each chart's polling loop only makes sense while its own pane is visible —
                        // stop it the moment the user clicks away so we're not hammering the router
                        // for a chart nobody's looking at.
                        if (this.activeTab === 'traffic' && tab !== 'traffic') {
                            this.stopTrafficPolling();
                        }
                        if (this.activeTab === 'resource' && tab !== 'resource') {
                            this.stopResourcePolling();
                        }
                        this.activeTab = tab;
                        if (tab === 'traffic') {
                            this.initTraffic();
                        } else if (tab === 'resource') {
                            this.initResource();
                        } else if (tab === 'torch') {
                            this.initTorch();
                        } else if (tab === 'console') {
                            this.initTerminal();
                        } else if (this.tabData[tab] === null && !this.tabLoading[tab]) {
                            this.loadTab(tab);
                        }
                    },

                    async loadTab(tab) {
                        this.tabLoading[tab] = true;
                        this.tabError[tab] = null;
                        try {
                            const res = await fetch(this.endpoints[tab], { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            if (json.error) {
                                this.tabError[tab] = json.error;
                                this.tabData[tab] = [];
                            } else {
                                this.tabData[tab] = json.data;
                            }
                        } catch (e) {
                            this.tabError[tab] = 'Failed to fetch — router may be unreachable.';
                            this.tabData[tab] = [];
                        }
                        this.tabLoading[tab] = false;
                    },

                    async initTraffic() {
                        if (!this.trafficChart) {
                            await this.loadTrafficInterfaces();
                            // Alpine's x-show toggle (from the activeTab assignment in
                            // selectTab()) applies via its own reactivity queue, not
                            // synchronously — building the chart before that DOM update lands
                            // means Chart.js measures a still-hidden, zero-size canvas and never
                            // renders anything. $nextTick guarantees the pane is actually visible
                            // first.
                            this.$nextTick(() => {
                                this.buildTrafficChart();
                                this.startTrafficPolling();
                            });
                        } else {
                            this.startTrafficPolling();
                        }
                    },

                    async loadTrafficInterfaces() {
                        try {
                            const res = await fetch(this.endpoints.interfaceList, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            if (json.error) {
                                this.trafficError = json.error;
                                return;
                            }
                            // Loopback can't carry meaningful bandwidth — drop it from the picker.
                            this.trafficInterfaces = (json.data || []).filter(i => i.type !== 'loopback');
                            const running = this.trafficInterfaces.find(i => i.running === 'true');
                            this.trafficSelected = (running || this.trafficInterfaces[0] || {}).name || null;
                        } catch (e) {
                            this.trafficError = 'Could not load interface list.';
                        }
                    },

                    buildTrafficChart() {
                        const canvas = document.getElementById('trafficChart');
                        if (!canvas) return;
                        // Defensive: destroy any chart already bound to this canvas first.
                        // Confirmed via a real headless-browser render that this constructor can
                        // run twice for the same canvas (Chart.js then throws "Canvas is already
                        // in use" and refuses to render at all) — this guard makes rebuilding
                        // safe regardless of what triggers the second call, rather than only
                        // patching one specific re-entry path.
                        const existing = Chart.getChart(canvas);
                        if (existing) existing.destroy();
                        this.trafficChart = new Chart(canvas.getContext('2d'), {
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
                                    y: { beginAtZero: true, ticks: { callback: (v) => this.formatBits(v) } },
                                    x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
                                },
                                plugins: { legend: { position: 'top' } },
                            },
                        });
                    },

                    startTrafficPolling() {
                        this.stopTrafficPolling();
                        this.pollTraffic();
                        // 2s balances "feels live" against round-trip latency without hammering the router.
                        this.trafficPolling = setInterval(() => this.pollTraffic(), 2000);
                    },

                    stopTrafficPolling() {
                        if (this.trafficPolling) {
                            clearInterval(this.trafficPolling);
                            this.trafficPolling = null;
                        }
                    },

                    async pollTraffic() {
                        if (!this.trafficSelected || !this.trafficChart) return;
                        try {
                            const res = await fetch(`${this.endpoints.traffic}?interface=${encodeURIComponent(this.trafficSelected)}`, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            if (json.error) {
                                this.trafficError = json.error;
                                return;
                            }
                            this.trafficError = null;
                            const row = (json.data && json.data[0]) || {};
                            this.rxNow = parseInt(row['rx-bits-per-second'] || 0);
                            this.txNow = parseInt(row['tx-bits-per-second'] || 0);

                            const chart = this.trafficChart;
                            chart.data.labels.push(new Date().toLocaleTimeString());
                            chart.data.datasets[0].data.push(this.rxNow);
                            chart.data.datasets[1].data.push(this.txNow);
                            // Keep a rolling one-minute window (30 points @ 2s) — enough to see a
                            // trend without the chart growing unbounded on a page left open.
                            if (chart.data.labels.length > 30) {
                                chart.data.labels.shift();
                                chart.data.datasets[0].data.shift();
                                chart.data.datasets[1].data.shift();
                            }
                            chart.update('none');
                        } catch (e) {
                            this.trafficError = 'Failed to fetch — router may be unreachable.';
                        }
                    },

                    async initResource() {
                        if (!this.resourceChart) {
                            // Same Alpine x-show/Chart.js timing issue as initTraffic() — this
                            // tab is only ever entered via a later click (never the default
                            // tab), so it's the one most likely to hit a still-hidden canvas.
                            this.$nextTick(() => {
                                this.buildResourceChart();
                                this.startResourcePolling();
                            });
                        } else {
                            this.startResourcePolling();
                        }
                    },

                    buildResourceChart() {
                        const canvas = document.getElementById('resourceChart');
                        if (!canvas) return;
                        const existing = Chart.getChart(canvas);
                        if (existing) existing.destroy();
                        this.resourceChart = new Chart(canvas.getContext('2d'), {
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
                    },

                    startResourcePolling() {
                        this.stopResourcePolling();
                        this.pollResource();
                        this.resourcePolling = setInterval(() => this.pollResource(), 2000);
                    },

                    stopResourcePolling() {
                        if (this.resourcePolling) {
                            clearInterval(this.resourcePolling);
                            this.resourcePolling = null;
                        }
                    },

                    async pollResource() {
                        if (!this.resourceChart) return;
                        try {
                            const res = await fetch(this.endpoints.resource, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            if (json.error) {
                                this.resourceError = json.error;
                                return;
                            }
                            this.resourceError = null;
                            const row = (json.data && json.data[0]) || {};
                            this.cpuNow = parseInt(row['cpu-load'] || 0);
                            this.memNow = parseInt(row['free-memory'] || 0);

                            const chart = this.resourceChart;
                            chart.data.labels.push(new Date().toLocaleTimeString());
                            chart.data.datasets[0].data.push(this.cpuNow);
                            chart.data.datasets[1].data.push(Math.round(this.memNow / 1048576));
                            if (chart.data.labels.length > 30) {
                                chart.data.labels.shift();
                                chart.data.datasets[0].data.shift();
                                chart.data.datasets[1].data.shift();
                            }
                            chart.update('none');
                        } catch (e) {
                            this.resourceError = 'Failed to fetch — router may be unreachable.';
                        }
                    },

                    async initTorch() {
                        if (this.trafficInterfaces.length === 0) {
                            await this.loadTrafficInterfaces();
                        }
                        if (!this.torchSelected) this.torchSelected = this.trafficSelected;
                    },

                    async scanTorch() {
                        if (!this.torchSelected) return;
                        this.torchScanning = true;
                        this.torchError = null;
                        try {
                            const res = await fetch(`${this.endpoints.torch}?interface=${encodeURIComponent(this.torchSelected)}`, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            if (json.error) {
                                this.torchError = json.error;
                                this.torchRows = [];
                            } else {
                                this.torchRows = json.data || [];
                            }
                        } catch (e) {
                            this.torchError = 'Failed to fetch — router may be unreachable.';
                            this.torchRows = [];
                        }
                        this.torchScanning = false;
                    },

                    changeTrafficInterface() {
                        // Clear history on interface switch so the graph doesn't show a
                        // discontinuous jump between two unrelated interfaces' traffic levels.
                        if (this.trafficChart) {
                            this.trafficChart.data.labels = [];
                            this.trafficChart.data.datasets[0].data = [];
                            this.trafficChart.data.datasets[1].data = [];
                            this.trafficChart.update('none');
                        }
                        this.trafficError = null;
                    },

                    formatBits(bps) {
                        bps = Number(bps) || 0;
                        if (bps >= 1e9) return (bps / 1e9).toFixed(2) + ' Gbps';
                        if (bps >= 1e6) return (bps / 1e6).toFixed(2) + ' Mbps';
                        if (bps >= 1e3) return (bps / 1e3).toFixed(1) + ' Kbps';
                        return bps + ' bps';
                    },

                    async initTerminal() {
                        if (this.terminalHistoryLoaded) return;
                        this.terminalHistoryLoaded = true;
                        try {
                            const res = await fetch(this.endpoints.terminalHistory, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            this.terminalHistory = json.data || [];
                        } catch (e) {
                            // History is a nice-to-have — leave it empty rather than blocking the console.
                        }
                    },

                    async runTerminalCommand() {
                        const command = this.terminalInput.trim();
                        if (!command) return;
                        if (/\b(remove|reset|reboot)\b/i.test(command) && !confirm(`Run this against live hardware?\n\n${command}`)) {
                            return;
                        }

                        this.terminalRunning = true;
                        try {
                            const res = await fetch(this.endpoints.terminalExecute, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ command }),
                            });
                            const json = await res.json();
                            this.terminalOutput.push({
                                command,
                                success: !json.error,
                                result: json.error || JSON.stringify(json.data, null, 2),
                            });
                        } catch (e) {
                            this.terminalOutput.push({ command, success: false, result: 'Failed to reach the router.' });
                        }
                        this.terminalInput = '';
                        this.terminalRunning = false;
                        this.terminalHistoryLoaded = false;
                        this.initTerminal();
                        this.$nextTick(() => {
                            const el = this.$refs.terminalScrollback;
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    },

                    showActionMessage(message, isError) {
                        this.actionMessage = message;
                        this.actionMessageIsError = isError;
                        setTimeout(() => { this.actionMessage = null; }, 4000);
                    },

                    async postAction(url, body) {
                        this.actionBusy = true;
                        try {
                            const res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(body || {}),
                            });
                            const json = await res.json();
                            if (json.error) {
                                this.showActionMessage(json.error, true);
                                return false;
                            }
                            this.showActionMessage(json.message || 'Done.', false);
                            return true;
                        } catch (e) {
                            this.showActionMessage('Failed to reach the router.', true);
                            return false;
                        } finally {
                            this.actionBusy = false;
                        }
                    },

                    async rebootRouter() {
                        if (!confirm('Reboot ' + '{{ $router->name }}' + '? It will be unreachable for about a minute.')) return;
                        this.rebooting = true;
                        await this.postAction(this.endpoints.reboot);
                        this.rebooting = false;
                    },

                    async toggleInterface(row) {
                        const disable = row['disabled'] !== 'true';
                        if (disable && !confirm(`Disable interface ${row['name']}? Anything connected through it will drop.`)) return;
                        const ok = await this.postAction(this.endpoints.toggleInterface, { interface: row['name'], disabled: disable });
                        if (ok) this.loadTab('ethernet');
                    },

                    async disconnectHotspotUser(row) {
                        if (!confirm(`Disconnect ${row['user'] || row['address']}?`)) return;
                        const ok = await this.postAction(this.endpoints.disconnectHotspot, { id: row['.id'] });
                        if (ok) this.loadTab('hotspot');
                    },

                    async disconnectPppoeUser(row) {
                        if (!confirm(`Disconnect ${row['name'] || row['address']}?`)) return;
                        const ok = await this.postAction(this.endpoints.disconnectPppoe, { id: row['.id'] });
                        if (ok) this.loadTab('pppoe');
                    },

                    async blockHotspotUser(row) {
                        if (!confirm(`Block ${row['address']} (${row['user'] || 'unknown user'})? They will be denied access until unblocked in Winbox.`)) return;
                        const ok = await this.postAction(this.endpoints.blockHotspot, { address: row['address'] });
                        if (ok) this.loadTab('hotspot');
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

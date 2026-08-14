<x-sidebar-layout title="Routers">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hardware &amp; Routers</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Live monitoring and management of all deployed Mikrotik routers.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('routers.noc') }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-grid-alt text-lg"></i> Fleet Status
            </a>
            <a href="{{ route('routers.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-link text-lg"></i> Deploy New Hardware
            </a>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or VPN IP..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <select name="status" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
            <option value="">All Statuses</option>
            @foreach(['active', 'pending', 'provisioning', 'offline'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <x-per-page-select />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('routers.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Board</th>
                        <th class="px-6 py-4">Hardware Identity</th>
                        <th class="px-6 py-4">VPN IP (Uplink)</th>
                        <th class="px-6 py-4">Access</th>
                        <th class="px-6 py-4 text-center">Connection Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($routers as $router)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors" x-data="{ copied: false }">
                            <td class="px-6 py-4">
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
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <a href="{{ route('routers.show', $router) }}" class="text-gray-900 dark:text-white font-bold hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ $router->name }}</a>
                                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">{{ $models[$router->board_model]['label'] ?? 'Unknown Model' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded border border-blue-100 dark:border-blue-900/50">
                                    {{ $router->ip_address }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3 text-xs">
                                    @if($router->web_proxy_port)
                                        <a href="http://{{ config('vpn.public_ip') }}:{{ $router->web_proxy_port }}" target="_blank" class="text-gray-400 hover:text-blue-600 transition-colors" title="Open Web Access">
                                            <i class="bx bx-globe text-lg"></i>
                                        </a>
                                    @endif
                                    @if($router->winbox_proxy_port)
                                        <button type="button" @click="navigator.clipboard.writeText('{{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }}'); copied = true; setTimeout(() => copied = false, 2000)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Copy Winbox Address">
                                            <i class="bx" :class="copied ? 'bx-check text-green-500' : 'bx-copy'"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('routers.monitor', $router) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Live Monitor">
                                        <i class="bx bx-pulse text-lg"></i>
                                    </a>
                                    <a href="{{ route('routers.show', $router) }}#captive-portal" class="text-gray-400 hover:text-blue-600 transition-colors" title="Captive Portal">
                                        <i class="bx bx-globe text-lg"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($router->status === 'active')
                                    <x-status-badge color="green" dot pulse>Online</x-status-badge>
                                @elseif($router->status === 'provisioning' || $router->status === 'pending')
                                    <x-status-badge color="amber" icon="bx-loader-alt bx-spin">Awaiting Uplink</x-status-badge>
                                @else
                                    <x-status-badge color="red" dot>Offline</x-status-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($router->status === 'pending' || $router->status === 'provisioning')
                                    <a href="{{ route('routers.provision', $router->id) }}" class="text-xs text-amber-600 hover:text-amber-700 font-bold tracking-wide uppercase transition-colors inline-flex items-center gap-1">
                                        Resume Setup <i class='bx bx-right-arrow-alt text-lg'></i>
                                    </a>
                                @else
                                    <div class="flex items-center justify-end gap-3" x-data="{ testing: false, result: null }">
                                        <a href="{{ route('routers.show', $router) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Router Settings">
                                            <i class='bx bx-cog text-lg'></i>
                                        </a>
                                        <button type="button" title="Test Connection" class="text-gray-400 hover:text-green-600 transition-colors"
                                            @click="
                                                testing = true; result = null;
                                                fetch('{{ route('routers.test-connection', $router) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                                                    .then(r => r.json())
                                                    .then(data => { result = data.status === 'online'; })
                                                    .catch(() => { result = false; })
                                                    .finally(() => { testing = false; setTimeout(() => result = null, 3000); })
                                            ">
                                            <i class='bx text-lg' :class="testing ? 'bx-loader-alt bx-spin' : (result === true ? 'bx-check text-green-500' : (result === false ? 'bx-x text-red-500' : 'bx-broadcast'))"></i>
                                        </button>
                                        <a href="{{ route('routers.show', $router) }}#decommission" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove Hardware (requires a confirmation code)">
                                            <i class='bx bx-trash text-lg'></i>
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class='bx bx-radar text-4xl mb-3 text-gray-200'></i>
                                <p class="text-xs tracking-widest uppercase">No hardware detected in the network topology.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $routers->links() }}</div>
</x-sidebar-layout>

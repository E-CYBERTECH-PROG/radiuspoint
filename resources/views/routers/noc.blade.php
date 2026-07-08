<x-sidebar-layout title="Fleet Status">
    @php
        $counts = ['active' => 0, 'offline' => 0, 'other' => 0];
        foreach ($routers as $r) {
            if ($r->status === 'active') $counts['active']++;
            elseif ($r->status === 'offline') $counts['offline']++;
            else $counts['other']++;
        }
    @endphp
    <div>
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fleet Status</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Every router at a glance &mdash; updated every minute automatically.</p>
            </div>
            <a href="{{ route('routers.index') }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-list-ul text-lg"></i> Table View
            </a>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-950 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <p class="text-3xl font-fira font-extrabold text-green-600 dark:text-green-400">{{ $counts['active'] }}</p>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Online</p>
            </div>
            <div class="bg-white dark:bg-gray-950 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <p class="text-3xl font-fira font-extrabold text-red-600 dark:text-red-400">{{ $counts['offline'] }}</p>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Offline</p>
            </div>
            <div class="bg-white dark:bg-gray-950 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <p class="text-3xl font-fira font-extrabold text-amber-600 dark:text-amber-400">{{ $counts['other'] }}</p>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Awaiting Uplink</p>
            </div>
        </div>

        @if($routers->isEmpty())
            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-12 text-center text-gray-400">
                <i class='bx bx-radar text-4xl mb-3 text-gray-200'></i>
                <p class="text-xs tracking-widest uppercase">No hardware deployed yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($routers as $router)
                    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-5"
                         x-data="{
                            live: null,
                            refreshing: false,
                            async refresh() {
                                this.refreshing = true;
                                try {
                                    const res = await fetch('{{ route('routers.test-connection', $router) }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    });
                                    this.live = await res.json();
                                } catch (e) {
                                    // Leave `live` as-is — the status badge already reflects DB state.
                                } finally {
                                    this.refreshing = false;
                                }
                            },
                         }">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3">
                                <x-router-board
                                    :port-count="$models[$router->board_model]['ports'] ?? 5"
                                    :sfp-count="$models[$router->board_model]['sfp'] ?? 0"
                                    :image="$models[$router->board_model]['image'] ?? null"
                                    :status="$router->status"
                                    compact
                                />
                                <div>
                                    <a href="{{ route('routers.show', $router) }}" class="text-gray-900 dark:text-white font-bold hover:text-blue-600 dark:hover:text-blue-400 transition-colors block">{{ $router->name }}</a>
                                    <span class="text-[10px] text-gray-400 font-fira">{{ $router->ip_address }}</span>
                                </div>
                            </div>
                            @if($router->status === 'active')
                                <span class="inline-flex items-center gap-1.5 text-[10px] text-green-700 dark:text-green-400 uppercase tracking-widest bg-green-50 dark:bg-green-900/20 px-2.5 py-1 rounded-full border border-green-200 dark:border-green-900/50 font-bold shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Online
                                </span>
                            @elseif($router->status === 'offline')
                                <span class="inline-flex items-center gap-1.5 text-[10px] text-red-700 dark:text-red-400 uppercase tracking-widest bg-red-50 dark:bg-red-900/20 px-2.5 py-1 rounded-full border border-red-200 dark:border-red-900/50 font-bold shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Offline
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-[10px] text-amber-700 dark:text-amber-400 uppercase tracking-widest bg-amber-50 dark:bg-amber-900/20 px-2.5 py-1 rounded-full border border-amber-200 dark:border-amber-900/50 font-bold shrink-0">
                                    <i class='bx bx-loader-alt bx-spin'></i> {{ ucfirst($router->status) }}
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-gray-400 mb-3">
                            Last seen: {{ $router->last_seen ? $router->last_seen->diffForHumans() : 'never' }}
                        </div>

                        <template x-if="live">
                            <div class="grid grid-cols-2 gap-3 text-xs border-t border-gray-100 dark:border-gray-800 pt-3 mb-3">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">CPU</p>
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="(live.cpu_load ?? '—') + '%'"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Uptime</p>
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="live.uptime ?? '—'"></p>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="refresh()" :disabled="refreshing" class="flex-1 inline-flex items-center justify-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-400 hover:text-blue-600 border border-gray-200 dark:border-gray-700 py-2 rounded-lg transition-colors disabled:opacity-50">
                                <i class="bx" :class="refreshing ? 'bx-loader-alt bx-spin' : 'bx-refresh'"></i> Refresh Now
                            </button>
                            <a href="{{ route('routers.monitor', $router) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Live Monitor">
                                <i class="bx bx-pulse text-lg"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-sidebar-layout>

<x-sidebar-layout title="{{ $router->name }}">
    <div x-data="routerDetail()" x-init="init()">
        <div class="mb-6">
            <a href="{{ route('routers.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
                <i class="bx bx-left-arrow-alt text-lg"></i> Back to Routers
            </a>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $router->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $models[$router->board_model]['label'] ?? 'Unknown Model' }} &middot; {{ $router->ip_address }}</p>
                </div>
                <button @click="testConnection()" :disabled="testing" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    <i class="bx" :class="testing ? 'bx-loader-alt bx-spin' : 'bx-broadcast'"></i>
                    <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-1 bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center">
                <x-router-board
                    :port-count="$models[$router->board_model]['ports'] ?? 5"
                    :sfp-count="$models[$router->board_model]['sfp'] ?? 0"
                    :image="$models[$router->board_model]['image'] ?? null"
                    :status="$router->status"
                />
                <p class="text-xs text-gray-400 mt-3 text-center">Uplink LED reflects live connection status</p>
            </div>

            <div class="lg:col-span-2 bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Live System Info</h3>
                <div class="grid grid-cols-2 gap-4 text-sm" x-show="!loaded">
                    <p class="text-gray-400 col-span-2">Checking connection...</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm" x-show="loaded" x-cloak>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Identity</p>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="identity || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Detected Board</p>
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
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">CPU Load</p>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="cpuLoad ? cpuLoad + '%' : '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Memory Free</p>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="memoryText"></p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Web Access</p>
                        <a href="http://{{ $router->ip_address }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-bold inline-flex items-center gap-1">
                            http://{{ $router->ip_address }} <i class="bx bx-link-external"></i>
                        </a>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Winbox Address</p>
                        <button @click="copyWinbox()" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 text-sm font-bold font-fira inline-flex items-center gap-1">
                            {{ $router->ip_address }}:8291 <i class="bx" :class="copied ? 'bx-check text-green-500' : 'bx-copy'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Rename / Change Model</h3>
                <form action="{{ route('routers.update', $router) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Name</label>
                        <input type="text" name="name" required value="{{ $router->name }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Router Model</label>
                        <select name="board_model" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                            @foreach($models as $key => $model)
                                <option value="{{ $key }}" @selected($router->board_model === $key)>{{ $model['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg text-sm">Save Changes</button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-red-200 dark:border-red-900/50 shadow-sm">
                <h3 class="text-md font-bold text-red-600 dark:text-red-400 mb-2">Decommission Hardware</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Permanently removes this router from your network topology. This cannot be undone.</p>
                <form action="{{ route('routers.destroy', $router) }}" method="POST" onsubmit="return confirm('Decommission {{ $router->name }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-lg text-sm inline-flex items-center gap-2">
                        <i class="bx bx-trash"></i> Decommission
                    </button>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            function routerDetail() {
                return {
                    testing: false,
                    loaded: false,
                    liveStatus: '{{ $router->status }}',
                    identity: null,
                    boardDetected: null,
                    version: null,
                    uptime: null,
                    cpuLoad: null,
                    freeMemory: null,
                    totalMemory: null,
                    copied: false,

                    get memoryText() {
                        if (!this.freeMemory || !this.totalMemory) return '—';
                        const freeMb = Math.round(this.freeMemory / 1048576);
                        const totalMb = Math.round(this.totalMemory / 1048576);
                        return `${freeMb} MB / ${totalMb} MB`;
                    },

                    init() {
                        this.testConnection();
                    },

                    async testConnection() {
                        this.testing = true;
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
                            this.liveStatus = data.status === 'online' ? 'online' : 'offline';
                            this.identity = data.identity ?? null;
                            this.boardDetected = data.board_model_detected ?? null;
                            this.version = data.version ?? null;
                            this.uptime = data.uptime ?? null;
                            this.cpuLoad = data.cpu_load ?? null;
                            this.freeMemory = data.free_memory ?? null;
                            this.totalMemory = data.total_memory ?? null;
                            this.loaded = true;
                            this.updateBoardLed();
                        } catch (e) {
                            this.liveStatus = 'offline';
                            this.loaded = true;
                        } finally {
                            this.testing = false;
                        }
                    },

                    copyWinbox() {
                        navigator.clipboard.writeText('{{ $router->ip_address }}:8291');
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    },

                    updateBoardLed() {
                        const led = document.querySelector('.router-board-led');
                        if (!led) return;
                        led.classList.remove('bg-green-500', 'bg-red-500', 'bg-amber-500');
                        if (this.liveStatus === 'online') {
                            led.classList.add('bg-green-500');
                            led.style.animation = 'router-board-blink 1s steps(1) infinite';
                        } else {
                            led.classList.add('bg-red-500');
                            led.style.animation = 'none';
                        }
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

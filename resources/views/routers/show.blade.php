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
                <div class="flex items-center gap-3">
                    <a href="{{ route('routers.monitor', $router) }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                        <i class="bx bx-pulse text-lg"></i> Live Monitor
                    </a>
                    <button @click="testConnection()" :disabled="testing" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                        <i class="bx" :class="testing ? 'bx-loader-alt bx-spin' : 'bx-broadcast'"></i>
                        <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
                    </button>
                </div>
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

            <div class="lg:col-span-2 bg-white dark:bg-gray-950 p-6 rounded-xl border-0 shadow-[0_10px_24px_-20px_rgba(0,0,0,.32)] hover:shadow-[0_14px_28px_-20px_rgba(0,0,0,.4)] transition-shadow">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Live System Info</h3>
                <div class="grid grid-cols-2 gap-4 text-sm" x-show="!loaded">
                    <p class="text-gray-400 col-span-2">Checking connection...</p>
                </div>
                <div x-show="loaded" x-cloak class="flex flex-col sm:flex-row gap-6">
                    <div class="grid grid-cols-2 gap-4 text-sm flex-1">
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
                    </div>
                    <div class="flex items-start justify-center gap-6 shrink-0">
                        <x-gauge-ring percent-expr="cpuLoad" value-expr="cpuLoad !== null ? cpuLoad + '%' : '—'" color="#2563eb" label="CPU Load" :size="84" />
                        <x-gauge-ring percent-expr="memPercent" value-expr="memPercent !== null ? memPercent + '%' : '—'" detail-expr="memoryText" color="#16a34a" label="Memory Used" :size="84" />
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Web Access</p>
                        @if($router->web_proxy_port)
                            <a href="http://{{ config('vpn.public_ip') }}:{{ $router->web_proxy_port }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-bold inline-flex items-center gap-1">
                                Open Web UI <i class="bx bx-link-external"></i>
                            </a>
                        @else
                            <span class="text-xs text-gray-400">Not yet allocated — re-save this router to assign a proxy port.</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Winbox Address</p>
                        @if($router->winbox_proxy_port)
                            <button @click="copyWinbox()" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 text-sm font-bold font-fira inline-flex items-center gap-1">
                                {{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }} <i class="bx" :class="copied ? 'bx-check text-green-500' : 'bx-copy'"></i>
                            </button>
                            <p class="text-[10px] text-gray-400 mt-1">Paste this into Winbox's "Connect To" field — reachable from anywhere, not just this server.</p>
                        @else
                            <span class="text-xs text-gray-400">Not yet allocated — re-save this router to assign a proxy port.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div id="captive-portal" class="bg-white dark:bg-gray-950 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-900/50 shadow-sm mb-6 scroll-mt-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="bx bx-globe text-xl text-blue-600 dark:text-blue-400"></i>
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">Captive Portal</h3>
                    @if(! $captivePortal)
                        <span class="text-[10px] text-amber-700 dark:text-amber-400 uppercase tracking-widest bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-900/50 font-bold">Using default</span>
                    @endif
                </div>
                <a href="{{ route('captive.show', $router) }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                    Preview <i class="bx bx-link-external"></i>
                </a>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Customize the page hotspot customers see when they connect to this router's WiFi.</p>
            <form action="{{ route('routers.captive-portal.update', $router) }}" method="POST" class="space-y-4" x-data="{ template: '{{ old('template', $captivePortal->template ?? 'light-lumen') }}' }">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Template</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
                        @php
                            $templateOptions = [
                                'light-lumen' => ['label' => 'Light Lumen', 'swatch' => 'background:radial-gradient(circle at 30% 20%, #eef2ff, #f8fafc 70%);'],
                                'crystal' => ['label' => 'Frozen Crystal', 'swatch' => 'background:radial-gradient(circle at 30% 20%, #334155, #020617 70%);'],
                                'grid' => ['label' => 'Grid', 'swatch' => 'background-color:#fafafa;background-image:linear-gradient(#d1d5db 1px,transparent 1px),linear-gradient(90deg,#d1d5db 1px,transparent 1px);background-size:8px 8px;border:1px solid #111827;'],
                                'package' => ['label' => 'Package', 'swatch' => 'background:linear-gradient(135deg, #dbeafe, #fff);'],
                                'raw' => ['label' => 'Raw', 'swatch' => 'background:#fff;border:2px solid #111827;'],
                                'cyberpunk' => ['label' => 'Cyberpunk', 'swatch' => 'background:linear-gradient(135deg, #0a0014, #1a0533);box-shadow:inset 0 0 0 1px rgba(217,70,239,.4);'],
                                'lipa' => ['label' => 'Lipa na M-Pesa', 'swatch' => 'background:radial-gradient(circle at 30% 20%, #0f2417, #05100a 70%);box-shadow:inset 0 0 0 1px rgba(0,166,81,.5);'],
                            ];
                        @endphp
                        @foreach($templateOptions as $key => $opt)
                            <label class="border rounded-lg p-2.5 cursor-pointer text-center transition-colors" :class="template === '{{ $key }}' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'">
                                <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only">
                                <span class="block h-7 rounded-md mb-2" style="{{ $opt['swatch'] }}"></span>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $opt['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Logo URL <span class="text-gray-400 normal-case">(optional)</span></label>
                        <input type="url" name="logo_url" value="{{ old('logo_url', $captivePortal->logo_url ?? '') }}" placeholder="https://..." class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Brand Color</label>
                        <input type="color" name="primary_color" value="{{ old('primary_color', $captivePortal->primary_color ?? '#2563eb') }}" class="w-full h-11 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Plans Per Row</label>
                        @php $columnsPerRow = old('columns_per_row', $captivePortal->columns_per_row ?? ''); @endphp
                        <select name="columns_per_row" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer">
                            <option value="" @selected($columnsPerRow === '')>Auto (responsive)</option>
                            @foreach([1, 2, 3, 4] as $n)
                                <option value="{{ $n }}" @selected((string) $columnsPerRow === (string) $n)>{{ $n }} per row</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 h-11 cursor-pointer">
                        <input type="checkbox" name="show_speed" value="1" @checked(old('show_speed', $captivePortal->show_speed ?? true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Show plan speeds</span>
                    </label>
                    <label class="flex items-center gap-2 h-11 cursor-pointer">
                        <input type="checkbox" name="show_navbar" value="1" @checked(old('show_navbar', $captivePortal->show_navbar ?? false)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Show top navbar</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Notice Title <span class="text-gray-400 normal-case">(optional)</span></label>
                    <input type="text" name="notice_title" value="{{ old('notice_title', $captivePortal->notice_title ?? '') }}" placeholder="e.g. Scheduled maintenance" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Notice Body <span class="text-gray-400 normal-case">(optional)</span></label>
                    <textarea name="notice_body" rows="2" placeholder="Tell your customers what's happening..." class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">{{ old('notice_body', $captivePortal->notice_body ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Testimonials <span class="text-gray-400 normal-case">(optional — only shown if filled in, use real customer quotes)</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <input type="text" name="testimonial_1_text" value="{{ old('testimonial_1_text', $captivePortal->testimonial_1_text ?? '') }}" placeholder="&quot;Fast and reliable, never lets me down.&quot;" maxlength="255" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            <input type="text" name="testimonial_1_author" value="{{ old('testimonial_1_author', $captivePortal->testimonial_1_author ?? '') }}" placeholder="— Jane, Eldoret" maxlength="100" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                        </div>
                        <div class="space-y-2">
                            <input type="text" name="testimonial_2_text" value="{{ old('testimonial_2_text', $captivePortal->testimonial_2_text ?? '') }}" placeholder="&quot;Best hotspot in the estate.&quot;" maxlength="255" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            <input type="text" name="testimonial_2_author" value="{{ old('testimonial_2_author', $captivePortal->testimonial_2_author ?? '') }}" placeholder="— David, Langas" maxlength="100" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                        </div>
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg text-sm">Save Portal Settings</button>
            </form>

            <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800" x-data="{ pushing: false, pushResult: null, pushOk: true }">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Sync Portal to Router</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pushes this portal to the router's hardware — run once, or again after a factory reset.</p>
                    </div>
                    <button type="button" @click="
                        pushing = true; pushResult = null;
                        fetch('{{ route('routers.captive-portal.push', $router) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                            .then(r => r.json().then(data => ({ ok: r.ok, data })))
                            .then(({ ok, data }) => { pushOk = ok; pushResult = ok ? ('Walled garden: ' + data.walled_garden + '. Hotspot files pushed: ' + data.hotspot_files_pushed + '. Login method fixed on ' + data.login_by_fixed + ' profile(s). RADIUS address fixed on ' + data.radius_address_fixed + ' entr(ies). One-session-per-host fixed on ' + data.one_session_per_host_fixed + ' PPPoE server(s).') : data.message; })
                            .catch(() => { pushOk = false; pushResult = 'Network error — could not reach the router.'; })
                            .finally(() => pushing = false);
                    " :disabled="pushing" class="shrink-0 inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs py-2 px-4 rounded-lg disabled:opacity-50 transition-colors">
                        <i class="bx" :class="pushing ? 'bx-loader-alt bx-spin' : 'bx-upload'"></i>
                        <span x-text="pushing ? 'Pushing...' : 'Push Files to Router'"></span>
                    </button>
                </div>
                <p x-show="pushResult" x-cloak class="text-xs font-bold mt-2" :class="pushOk ? 'text-green-600 dark:text-green-400' : 'text-red-500'" x-text="pushResult"></p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm mb-6">
            <h3 class="text-md font-bold text-gray-900 dark:text-white mb-1">Reprovision</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">For a factory reset, a swapped-in replacement unit, or just re-syncing everything from scratch. Both are safe to run on an already-configured router — nothing here is destructive.</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('routers.provision', $router) }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    <i class="bx bx-terminal text-lg"></i> Re-run Bootstrap Script
                </a>
                <a href="{{ route('routers.ports', $router) }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    <i class="bx bx-network-chart text-lg"></i> Reconfigure Ports
                </a>
            </div>
            <p class="text-xs text-gray-400 mt-3">Bootstrap script re-establishes the tunnel/RADIUS wiring (needs the router reachable to paste it into). Reconfigure Ports needs the router already online, and re-pushes hotspot/PPPoE/captive-portal/free-mode for whatever roles you assign.</p>
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

            <div id="decommission" class="bg-white dark:bg-gray-950 p-6 rounded-xl border border-red-200 dark:border-red-900/50 shadow-sm scroll-mt-6">
                <h3 class="text-md font-bold text-red-600 dark:text-red-400 mb-2">Decommission Hardware</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Permanently removes this router from your network topology. This cannot be undone.</p>

                <template x-if="decommissionStep === 'idle'">
                    <button type="button" @click="startDecommission()" :disabled="decommissionSending" class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold py-2.5 px-5 rounded-lg text-sm inline-flex items-center gap-2">
                        <i class="bx" :class="decommissionSending ? 'bx-loader-alt bx-spin' : 'bx-trash'"></i>
                        <span x-text="decommissionSending ? 'Sending code...' : 'Decommission'"></span>
                    </button>
                </template>

                <template x-if="decommissionStep === 'code'">
                    <div class="space-y-3 max-w-sm">
                        <p class="text-sm text-gray-700 dark:text-gray-300">We sent a 6-digit code to your notifications (in-app + push). Enter it to confirm removal of <strong>{{ $router->name }}</strong>.</p>
                        <input type="text" x-model="decommissionCode" placeholder="000000" inputmode="numeric" maxlength="6" class="w-full text-center tracking-widest font-fira text-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-red-500 outline-none">
                        <p x-show="decommissionError" x-cloak x-text="decommissionError" class="text-sm text-red-500 font-bold"></p>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="confirmDecommission()" :disabled="decommissionSending || decommissionCode.length !== 6" class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold py-2.5 px-5 rounded-lg text-sm inline-flex items-center gap-2">
                                <i class="bx" :class="decommissionSending ? 'bx-loader-alt bx-spin' : 'bx-check'"></i>
                                <span x-text="decommissionSending ? 'Verifying...' : 'Confirm Removal'"></span>
                            </button>
                            <button type="button" @click="decommissionStep = 'idle'; decommissionCode = ''; decommissionError = null" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">Cancel</button>
                        </div>
                    </div>
                </template>
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

                    decommissionStep: 'idle',
                    decommissionCode: '',
                    decommissionError: null,
                    decommissionSending: false,

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
                        navigator.clipboard.writeText('{{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }}');
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

                    async startDecommission() {
                        this.decommissionSending = true;
                        this.decommissionError = null;
                        try {
                            const res = await fetch("{{ route('routers.decommission.request', $router) }}", {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            });
                            if (!res.ok) throw new Error();
                            this.decommissionStep = 'code';
                        } catch (e) {
                            this.decommissionError = 'Could not send a code. Please try again.';
                        } finally {
                            this.decommissionSending = false;
                        }
                    },

                    async confirmDecommission() {
                        this.decommissionSending = true;
                        this.decommissionError = null;
                        try {
                            const res = await fetch("{{ route('routers.decommission.confirm', $router) }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                body: JSON.stringify({ code: this.decommissionCode }),
                            });
                            const data = await res.json();
                            if (!res.ok) {
                                this.decommissionError = data.message || 'That code is incorrect or has expired.';
                                this.decommissionSending = false;
                                return;
                            }
                            window.location.href = data.redirect;
                        } catch (e) {
                            this.decommissionError = 'Network error. Please try again.';
                            this.decommissionSending = false;
                        }
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

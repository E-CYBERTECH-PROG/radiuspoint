{{--
    Global slide-over quick-detail panel — click a username anywhere (Hotspot/PPPoE Users lists,
    dashboard recent transactions) to see status/usage/expiry and act on the account (Extend,
    Force Disconnect, Reset MAC) without leaving the current page. Included once in the shared
    layout so every page gets it "for free" just by dispatching a window `open-user-panel` event
    with a `panelUrl` (a route to HotspotUserController::panel()/PppoeUserController::panel()).
--}}
<div
    x-data="userDetailPanel()"
    @open-user-panel.window="open($event.detail.panelUrl)"
    x-show="visible"
    x-cloak
    class="fixed inset-0 z-[60]"
>
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-[1px]" x-show="visible" x-transition.opacity @click="close()"></div>

    <div
        class="absolute right-0 top-0 h-full w-full sm:w-96 max-w-full bg-white dark:bg-gray-950 border-l border-gray-200 dark:border-gray-800 shadow-2xl flex flex-col"
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @click.outside="close()"
    >
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400" x-text="data?.type === 'pppoe' ? 'PPPoE Customer' : 'Hotspot Customer'"></p>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-fira truncate" x-text="data?.title || '—'"></h3>
            </div>
            <button @click="close()" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 shrink-0 ml-3"><i class="bx bx-x text-2xl"></i></button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-6">
            <template x-if="loading">
                <div class="flex items-center justify-center py-16 text-gray-400">
                    <i class="bx bx-loader-alt bx-spin text-3xl"></i>
                </div>
            </template>

            <template x-if="error">
                <p class="text-sm text-red-500" x-text="error"></p>
            </template>

            <template x-if="!loading && data">
                <div class="space-y-6">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 text-[10px] uppercase tracking-widest px-3 py-1 rounded-full border font-bold"
                              :class="{
                                  'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-900/50': data.status === 'active',
                                  'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-900/50': data.status === 'expired',
                                  'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-900/50': data.status === 'offline',
                              }" x-text="data.status"></span>
                        <template x-if="data.usage?.throttled">
                            <span class="inline-flex items-center gap-1 text-[10px] uppercase tracking-widest px-3 py-1 rounded-full border font-bold text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-900/50">
                                <i class="bx bx-tachometer"></i> FUP Throttled
                            </span>
                        </template>
                        <span x-show="subtitle_text" class="text-xs text-gray-500 dark:text-gray-400" x-text="subtitle_text"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Router</p>
                            <p class="text-gray-900 dark:text-white font-bold" x-text="data.router_name || '—'"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Expiry</p>
                            <p class="text-gray-900 dark:text-white font-bold" x-text="data.expires_at || '—'"></p>
                            <p class="text-xs text-gray-400" x-text="data.expires_human || ''"></p>
                        </div>
                        <template x-if="data.phone_number">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Phone</p>
                                <p class="text-gray-900 dark:text-white font-bold" x-text="data.phone_number"></p>
                            </div>
                        </template>
                        <template x-if="data.mac_address">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">MAC</p>
                                <p class="text-gray-900 dark:text-white font-bold font-fira text-xs" x-text="data.mac_address"></p>
                            </div>
                        </template>
                    </div>

                    <template x-if="data.usage">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Data Usage This Cycle</p>
                            <p class="text-sm font-fira font-bold text-gray-900 dark:text-white">
                                <span x-text="data.usage.used_mb.toLocaleString()"></span> MB
                                <template x-if="data.usage.cap_mb"><span> / <span x-text="data.usage.cap_mb.toLocaleString()"></span> MB</span></template>
                            </p>
                            <template x-if="data.usage.percent !== null">
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full" :class="data.usage.percent >= 100 ? 'bg-red-500' : (data.usage.percent >= 80 ? 'bg-amber-500' : 'bg-blue-500')" :style="`width: ${data.usage.percent}%`"></div>
                                </div>
                            </template>
                            <p class="text-xs text-gray-400 mt-1">Since <span x-text="data.usage.cycle_start"></span></p>
                        </div>
                    </template>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 space-y-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Extend</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click="extend(1)" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors">+1 Day</button>
                                <button type="button" @click="extend(7)" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors">+7 Days</button>
                                <button type="button" @click="extend(30)" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors">+30 Days</button>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="disconnect()" :disabled="acting" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:text-amber-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors inline-flex items-center gap-1.5 disabled:opacity-50">
                                <i class="bx bx-power-off"></i> Force Disconnect
                            </button>
                            <button type="button" x-show="data.type === 'hotspot'" @click="resetMac()" :disabled="acting" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:text-amber-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors inline-flex items-center gap-1.5 disabled:opacity-50">
                                <i class="bx bx-reset"></i> Reset MAC
                            </button>
                        </div>

                        <template x-if="message">
                            <p class="text-xs font-bold" :class="messageOk ? 'text-green-600' : 'text-red-500'" x-text="message"></p>
                        </template>
                    </div>

                    <a :href="data.edit_url" class="block text-center text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 pt-2">
                        View Full Profile <i class="bx bx-right-arrow-alt align-middle"></i>
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    function userDetailPanel() {
        return {
            visible: false,
            loading: false,
            acting: false,
            data: null,
            error: null,
            message: null,
            messageOk: true,
            panelUrl: null,

            get subtitle_text() {
                return this.data?.subtitle || '';
            },

            async open(panelUrl) {
                this.panelUrl = panelUrl;
                this.visible = true;
                this.loading = true;
                this.error = null;
                this.message = null;
                this.data = null;

                try {
                    const res = await fetch(panelUrl, { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) throw new Error('failed');
                    this.data = await res.json();
                } catch (e) {
                    this.error = 'Could not load customer details.';
                } finally {
                    this.loading = false;
                }
            },

            close() {
                this.visible = false;
            },

            async refresh() {
                if (! this.panelUrl) return;
                try {
                    const res = await fetch(this.panelUrl, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) this.data = await res.json();
                } catch (e) {
                    // Keep showing the last-known data — the action's own message already reported success/failure.
                }
            },

            async runAction(url, body = {}) {
                if (! url || this.acting) return;
                this.acting = true;
                this.message = null;

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(body),
                    });
                    const json = await res.json().catch(() => ({}));
                    this.message = json.message || (res.ok ? 'Done.' : 'Action failed.');
                    this.messageOk = res.ok;
                    if (res.ok) await this.refresh();
                } catch (e) {
                    this.message = 'Action failed — check your connection.';
                    this.messageOk = false;
                } finally {
                    this.acting = false;
                }
            },

            extend(days) {
                this.runAction(this.data?.extend_url, { days });
            },

            disconnect() {
                if (! confirm('Disconnect this customer\'s active session now?')) return;
                this.runAction(this.data?.disconnect_url);
            },

            resetMac() {
                if (! confirm('Clear this customer\'s bound MAC address? The next device to connect will bind automatically.')) return;
                this.runAction(this.data?.reset_mac_url);
            },
        };
    }
</script>

<x-sidebar-layout title="{{ $olt->name }}">
    <div x-data="oltConsole()" x-init="loadHistory()">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <a href="{{ route('olts.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
                    <i class="bx bx-left-arrow-alt text-lg"></i> Back to OLT Devices
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $olt->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-fira">{{ $olt->ip_address }}:{{ $olt->ssh_port }} &middot; {{ strtoupper($olt->brand) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="testConnection()" :disabled="testing" class="inline-flex items-center gap-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-4 rounded-lg shadow-sm transition-colors disabled:opacity-50">
                    <i class="bx" :class="testing ? 'bx-loader-alt bx-spin' : 'bx-wifi-2'"></i> Test Connection
                </button>
                <form action="{{ route('olts.destroy', $olt) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this OLT?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-4 rounded-lg shadow-sm transition-colors">
                        <i class="bx bx-trash text-lg"></i> Remove
                    </button>
                </form>
            </div>
        </div>

        <template x-if="connectionMessage">
            <div class="mb-6 flex items-center gap-2 text-sm font-bold px-4 py-3 rounded-lg" :class="connectionOk ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-900/50' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-900/50'">
                <i class="bx" :class="connectionOk ? 'bxs-check-circle' : 'bxs-error-circle'"></i>
                <span x-text="connectionMessage"></span>
            </div>
        </template>

        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900/50 text-amber-700 dark:text-amber-400 text-sm px-4 py-3 rounded-lg mb-6">
            <i class="bx bx-info-circle align-middle"></i>
            This is a raw remote terminal — commands are sent to the OLT's own CLI exactly as typed and its real output is shown below, unlike the Router console which speaks a structured API. There is no confirmed command reference for VSOL/Hioso ONU provisioning yet, so treat this like a direct SSH session and consult your OLT's own CLI manual for command syntax.
        </div>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
            <h2 class="text-md font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="bx bx-terminal"></i> Console</h2>

            <form @submit.prevent="runCommand()" class="flex items-center gap-2 mb-4">
                <span class="font-fira text-gray-400 text-sm">&gt;</span>
                <input type="text" x-model="commandInput" :disabled="running"
                       placeholder="e.g. show onu state  (consult your OLT's CLI manual for exact syntax)"
                       class="flex-1 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white font-fira text-sm py-2 px-3 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" :disabled="running || !commandInput" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-sm py-2 px-4 rounded-lg transition-colors">
                    <i class="bx" :class="running ? 'bx-loader-alt bx-spin' : 'bx-play'"></i> Run
                </button>
            </form>

            <div class="bg-gray-950 dark:bg-black rounded-lg p-4 font-fira text-xs text-gray-300 overflow-y-auto mb-4" style="max-height: 400px" x-ref="scrollback">
                <template x-if="output.length === 0">
                    <p class="text-gray-500">No commands run yet this session.</p>
                </template>
                <template x-for="(entry, i) in output" :key="i">
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
                            <template x-if="history.length === 0">
                                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No history yet.</td></tr>
                            </template>
                            <template x-for="(log, i) in history" :key="i">
                                <tr>
                                    <td class="px-4 py-2 font-fira text-gray-700 dark:text-gray-300" x-text="log.command"></td>
                                    <td class="px-4 py-2 text-gray-500" x-text="log.user"></td>
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
    </div>

    <x-slot name="scripts">
        <script>
            function oltConsole() {
                return {
                    commandInput: '',
                    output: [],
                    running: false,
                    history: [],
                    testing: false,
                    connectionMessage: null,
                    connectionOk: true,

                    async loadHistory() {
                        try {
                            const res = await fetch('{{ route('olts.terminal.history', $olt) }}', { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            this.history = json.data || [];
                        } catch (e) {
                            // History stays empty on a transient failure.
                        }
                    },

                    async runCommand() {
                        const command = this.commandInput.trim();
                        if (! command) return;

                        this.running = true;
                        try {
                            const res = await fetch('{{ route('olts.terminal.execute', $olt) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                body: JSON.stringify({ command }),
                            });
                            const json = await res.json();
                            this.output.push({ command, success: ! json.error, result: json.error || json.result || '(no output)' });
                        } catch (e) {
                            this.output.push({ command, success: false, result: 'Failed to reach the OLT.' });
                        } finally {
                            this.running = false;
                            this.commandInput = '';
                            this.loadHistory();
                            this.$nextTick(() => {
                                this.$refs.scrollback.scrollTop = this.$refs.scrollback.scrollHeight;
                            });
                        }
                    },

                    async testConnection() {
                        this.testing = true;
                        this.connectionMessage = null;
                        try {
                            const res = await fetch('{{ route('olts.test-connection', $olt) }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            });
                            const json = await res.json();
                            this.connectionOk = res.ok;
                            this.connectionMessage = json.message;
                        } catch (e) {
                            this.connectionOk = false;
                            this.connectionMessage = 'Could not reach the server.';
                        } finally {
                            this.testing = false;
                        }
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

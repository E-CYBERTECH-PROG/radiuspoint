<x-sidebar-layout title="{{ $olt->name }}">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <a href="{{ route('olts.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
                <i class="ti ti-arrow-left icon"></i> Back to OLT Devices
            </a>
            <h1 class="mb-1">{{ $olt->name }}</h1>
            <p class="text-muted font-monospace mb-0">{{ $olt->ip_address }}:{{ $olt->ssh_port }} &middot; {{ strtoupper($olt->brand) }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn" id="rp-olt-test">
                <i class="ti ti-wifi" id="rp-olt-test-icon"></i> Test Connection
            </button>
            <form action="{{ route('olts.destroy', $olt) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this OLT?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn">
                    <i class="ti ti-trash icon"></i> Remove
                </button>
            </form>
        </div>
    </div>

    <div class="alert d-none align-items-center gap-2" id="rp-olt-connection-alert">
        <i class="icon flex-shrink-0" id="rp-olt-connection-icon"></i>
        <span id="rp-olt-connection-message"></span>
    </div>

    <div class="alert alert-warning">
        <i class="ti ti-info-circle align-middle"></i>
        This is a raw remote terminal — commands are sent to the OLT's own CLI exactly as typed and its real output is shown below, unlike the Router console which speaks a structured API. There is no confirmed command reference for VSOL/Hioso ONU provisioning yet, so treat this like a direct SSH session and consult your OLT's own CLI manual for command syntax.
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="card-title d-flex align-items-center gap-2"><i class="ti ti-terminal-2"></i> Console</h2>

            <form id="rp-olt-command-form" class="d-flex align-items-center gap-2 mb-3">
                <span class="font-monospace text-muted">&gt;</span>
                <input type="text" id="rp-olt-command-input" placeholder="e.g. show onu state  (consult your OLT's CLI manual for exact syntax)" class="form-control font-monospace" autocomplete="off">
                <button type="submit" class="btn btn-primary" id="rp-olt-run-btn">
                    <i class="ti ti-player-play" id="rp-olt-run-icon"></i> Run
                </button>
            </form>

            <div class="bg-dark text-light font-monospace small rounded p-3 mb-3" style="max-height:400px;overflow-y:auto" id="rp-olt-scrollback">
                <p class="text-muted mb-0" id="rp-olt-output-empty">No commands run yet this session.</p>
            </div>

            <div>
                <p class="text-uppercase text-muted small mb-2">Recent History</p>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap">
                        <thead>
                            <tr>
                                <th>Command</th>
                                <th>By</th>
                                <th>When</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody id="rp-olt-history">
                            <tr><td colspan="4" class="text-center text-muted">No history yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            (function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                var output = [];
                var running = false;
                var testing = false;

                var scrollback = document.getElementById('rp-olt-scrollback');
                var outputEmpty = document.getElementById('rp-olt-output-empty');
                var historyBody = document.getElementById('rp-olt-history');
                var commandInput = document.getElementById('rp-olt-command-input');
                var runBtn = document.getElementById('rp-olt-run-btn');
                var runIcon = document.getElementById('rp-olt-run-icon');
                var testBtn = document.getElementById('rp-olt-test');
                var testIcon = document.getElementById('rp-olt-test-icon');
                var connAlert = document.getElementById('rp-olt-connection-alert');
                var connIcon = document.getElementById('rp-olt-connection-icon');
                var connMessage = document.getElementById('rp-olt-connection-message');

                function renderOutput() {
                    outputEmpty.style.display = output.length === 0 ? '' : 'none';
                    scrollback.innerHTML = '';
                    if (output.length === 0) scrollback.appendChild(outputEmpty);
                    output.forEach(function (entry) {
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
                    });
                    scrollback.scrollTop = scrollback.scrollHeight;
                }

                function renderHistory(history) {
                    if (!history || history.length === 0) {
                        historyBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No history yet.</td></tr>';
                        return;
                    }
                    historyBody.innerHTML = history.map(function (log) {
                        return '<tr>' +
                            '<td class="font-monospace">' + log.command + '</td>' +
                            '<td class="text-muted">' + log.user + '</td>' +
                            '<td class="text-muted">' + log.at + '</td>' +
                            '<td><i class="ti ' + (log.success ? 'ti-check text-success' : 'ti-x text-danger') + '"></i></td>' +
                            '</tr>';
                    }).join('');
                }

                async function loadHistory() {
                    try {
                        var res = await fetch('{{ route('olts.terminal.history', $olt) }}', { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        renderHistory(json.data || []);
                    } catch (e) {
                        // History stays empty on a transient failure.
                    }
                }

                async function runCommand(command) {
                    if (!command || running) return;
                    running = true;
                    runBtn.disabled = true;
                    commandInput.disabled = true;
                    runIcon.className = 'ti ti-loader-2 icon-spin';

                    try {
                        var res = await fetch('{{ route('olts.terminal.execute', $olt) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                            body: JSON.stringify({ command: command }),
                        });
                        var json = await res.json();
                        output.push({ command: command, success: !json.error, result: json.error || json.result || '(no output)' });
                    } catch (e) {
                        output.push({ command: command, success: false, result: 'Failed to reach the OLT.' });
                    } finally {
                        running = false;
                        runBtn.disabled = false;
                        commandInput.disabled = false;
                        runIcon.className = 'ti ti-player-play';
                        commandInput.value = '';
                        renderOutput();
                        loadHistory();
                    }
                }

                document.getElementById('rp-olt-command-form').addEventListener('submit', function (e) {
                    e.preventDefault();
                    runCommand(commandInput.value.trim());
                });

                testBtn.addEventListener('click', async function () {
                    if (testing) return;
                    testing = true;
                    testBtn.disabled = true;
                    testIcon.className = 'ti ti-loader-2 icon-spin';
                    connAlert.classList.add('d-none');

                    try {
                        var res = await fetch('{{ route('olts.test-connection', $olt) }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        });
                        var json = await res.json();
                        connAlert.className = 'alert d-flex align-items-center gap-2 ' + (res.ok ? 'alert-success' : 'alert-danger');
                        connIcon.className = 'icon flex-shrink-0 ti ' + (res.ok ? 'ti-circle-check-filled' : 'ti-alert-circle-filled');
                        connMessage.textContent = json.message;
                    } catch (e) {
                        connAlert.className = 'alert d-flex align-items-center gap-2 alert-danger';
                        connIcon.className = 'icon flex-shrink-0 ti ti-alert-circle-filled';
                        connMessage.textContent = 'Could not reach the server.';
                    } finally {
                        testing = false;
                        testBtn.disabled = false;
                        testIcon.className = 'ti ti-wifi';
                    }
                });

                loadHistory();
            })();
        </script>
    </x-slot>
</x-sidebar-layout>

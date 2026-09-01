<x-sidebar-layout title="Provision Router">
    <div class="mb-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-end justify-content-between gap-3">
        <div>
            <h1 class="mb-1">Connect Router</h1>
            <p class="text-muted mb-0">
                <span class="fw-bold">{{ $router->name }}</span> &middot; <span class="fw-bold">{{ $router->ip_address }}</span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 bg-amber-lt px-3 py-2 rounded">
            <span id="statusPulse" class="rounded-circle bg-amber" style="width:.625rem;height:.625rem"></span>
            <span id="statusText" class="text-warning text-uppercase small fw-bold">Waiting for Router</span>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card h-100 d-flex flex-column">
                <div class="card-header">
                    <span class="text-muted small fw-bold">Setup Script</span>
                    <div class="card-actions">
                        <button onclick="copyPayload()" id="copyBtn" class="btn btn-primary btn-sm">
                            <i class="ti ti-copy"></i> Copy Payload
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <p class="text-muted small mb-3 text-uppercase border-bottom pb-2 d-flex align-items-center gap-2">
                        <i class="ti ti-terminal-2"></i> Paste this sequence into MikroTik Terminal
                    </p>
                    <div class="bg-dark rounded p-3 custom-scrollbar" style="max-height:20rem;overflow-y:auto">
                        <pre id="payloadText" class="text-success font-monospace mb-0" style="font-size:.75rem;line-height:1.8;white-space:pre-wrap">{{ trim($script) }}</pre>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-link btn-sm p-0 d-flex align-items-center gap-2" data-bs-toggle="collapse" data-bs-target="#rp-provision-help">
                            <i class="ti ti-help-circle"></i> Can't find Terminal to paste this?
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="collapse mt-2" id="rp-provision-help">
                            <div class="bg-body-secondary rounded p-3 text-muted small">
                                A factory-fresh router often boots into Winbox's <strong>Quick Set</strong> screen instead of the full menu, which doesn't show Terminal by default. Look for the mode toggle in the top-right corner of the Winbox login window and switch it from <strong>Quick Set</strong> to <strong>Winbox</strong> &mdash; Terminal will then be in the left-hand menu.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100 d-flex flex-column">

                <div class="bg-dark d-flex align-items-center justify-content-center position-relative overflow-hidden border-bottom" style="height:10rem">
                    <div class="position-absolute rounded-circle border border-primary-subtle" style="width:14rem;height:14rem;opacity:.15"></div>
                    <div class="position-absolute rounded-circle border border-primary-subtle" style="width:10rem;height:10rem;opacity:.25"></div>
                    <div class="position-absolute rounded-circle border border-primary-subtle" style="width:6rem;height:6rem;opacity:.35"></div>
                    <div id="radarNode" class="rounded-circle bg-amber position-relative" style="width:1rem;height:1rem;z-index:1;transition:background-color .5s"></div>
                    <div id="radarSweep" class="position-absolute rounded-circle d-none" style="width:6rem;height:6rem;right:50%;bottom:50%;border-right:2px solid rgba(99,102,241,.7);animation:rp-radar-spin 3s linear infinite;transform-origin:bottom right"></div>
                </div>

                <div class="card-body border-bottom">
                    <div class="d-flex justify-content-between text-muted small fw-bold text-uppercase mb-2">
                        <span>Progress</span>
                        <span id="progressText">0%</span>
                    </div>
                    <div class="progress progress-sm">
                        <div id="progressBar" class="progress-bar bg-primary" style="width: 0%"></div>
                    </div>
                </div>

                <div class="card-body d-flex flex-column justify-content-end flex-fill">
                    <div id="liveLogs" class="d-flex flex-column justify-content-end gap-1 mb-4 bg-dark rounded p-3 font-monospace" style="min-height:7.5rem;font-size:.6875rem;line-height:1.6">
                        <div class="text-muted">&gt; System in standby state...</div>
                        <div class="text-muted">&gt; Watching for the script to complete on your router — no action needed here.</div>
                        <div id="lastCheckLog" class="text-info">&gt; _</div>
                    </div>

                    <button id="initiateBtn" onclick="executeHandshake(true)" class="btn btn-primary btn-lg text-uppercase">
                        <i class="ti ti-refresh"></i>
                        <span>Check Now</span>
                    </button>

                    <a href="{{ route('routers.ports', $router->id) }}" id="proceedBtn" class="btn btn-success btn-lg text-uppercase d-none">
                        Hardware Verified &mdash; Map Ports <i class="ti ti-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <x-slot name="scripts">
        <script>
            function copyPayload() {
                navigator.clipboard.writeText(document.getElementById('payloadText').innerText);
                const btn = document.getElementById('copyBtn');
                btn.innerHTML = "<i class='ti ti-check'></i> Copied to Clipboard";
                btn.className = 'btn btn-success btn-sm';

                setTimeout(() => {
                    btn.innerHTML = "<i class='ti ti-copy'></i> Copy Payload";
                    btn.className = 'btn btn-primary btn-sm';
                }, 3000);
            }

            let pollTimer = null;
            let verified = false;

            function startPolling() {
                pollTimer = setInterval(() => executeHandshake(false), 4000);
            }

            async function executeHandshake(manual) {
                if (verified) return;

                const btn = document.getElementById('initiateBtn');
                const logsBox = document.getElementById('liveLogs');
                const lastCheckLog = document.getElementById('lastCheckLog');
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const statusText = document.getElementById('statusText');
                const statusPulse = document.getElementById('statusPulse');
                const radarSweep = document.getElementById('radarSweep');
                const radarNode = document.getElementById('radarNode');

                if (manual) {
                    btn.disabled = true;
                    btn.querySelector('span').innerText = "Checking...";
                    btn.querySelector('i').className = 'ti ti-loader-2 icon-spin';

                    statusText.innerText = "SCANNING NETWORK";
                    statusText.className = 'text-primary text-uppercase small fw-bold';
                    statusPulse.className = 'rounded-circle bg-primary';
                    statusPulse.style.width = '.625rem';
                    statusPulse.style.height = '.625rem';

                    radarSweep.classList.remove('d-none');
                    radarNode.style.backgroundColor = 'var(--tblr-primary)';
                    progressBar.style.width = "50%";
                }

                try {
                    const response = await fetch("{{ route('routers.check-status', $router->id) }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        }
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        // SUCCESS STATE — reached the same way whether this was the manual
                        // button or a background poll tick that happened to land first.
                        verified = true;
                        if (pollTimer) clearInterval(pollTimer);

                        progressBar.style.width = "100%";
                        progressText.innerText = "100%";
                        progressBar.className = 'progress-bar bg-success';

                        radarSweep.classList.add('d-none');
                        radarNode.style.backgroundColor = 'var(--tblr-success)';

                        let finalLog = document.createElement('div');
                        finalLog.innerHTML = "&gt; <span class='text-success fw-bold'>[UPLINK VERIFIED] Hardware is online.</span>";
                        logsBox.appendChild(finalLog);

                        statusText.innerText = "HARDWARE ONLINE";
                        statusText.className = 'text-success text-uppercase small fw-bold';
                        statusPulse.className = 'rounded-circle bg-success';
                        statusPulse.style.width = '.625rem';
                        statusPulse.style.height = '.625rem';

                        btn.classList.add('d-none');
                        document.getElementById('proceedBtn').classList.remove('d-none');

                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    if (manual) {
                        // FAILED STATE — only shown for a manual check. A background poll tick
                        // that hasn't succeeded yet just means the router isn't there yet, not
                        // that anything has actually failed, so it stays quiet.
                        progressBar.className = 'progress-bar bg-danger';
                        radarSweep.classList.add('d-none');
                        radarNode.style.backgroundColor = 'var(--tblr-danger)';

                        let finalLog = document.createElement('div');
                        finalLog.innerHTML = "&gt; <span class='text-danger fw-bold'>[NOT YET] " + (error.message || "Hardware not reachable yet.") + "</span>";
                        logsBox.appendChild(finalLog);

                        statusText.innerText = "WAITING FOR ROUTER";
                        statusText.className = 'text-warning text-uppercase small fw-bold';
                        statusPulse.className = 'rounded-circle bg-amber';
                        statusPulse.style.width = '.625rem';
                        statusPulse.style.height = '.625rem';

                        btn.disabled = false;
                        btn.querySelector('span').innerText = "Check Now";
                        btn.querySelector('i').className = 'ti ti-refresh';
                    } else {
                        lastCheckLog.innerHTML = "&gt; Still waiting... last checked " + new Date().toLocaleTimeString();
                    }
                }
            }

            startPolling();
        </script>

        <style>
            @keyframes rp-radar-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>
    </x-slot>
</x-sidebar-layout>

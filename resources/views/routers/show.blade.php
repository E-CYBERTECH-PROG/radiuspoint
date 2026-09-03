<x-sidebar-layout title="{{ $router->name }}">
    <div class="mb-4">
        <a href="{{ route('routers.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to Routers
        </a>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
            <div>
                <h1 class="mb-1">{{ $router->name }}</h1>
                <p class="text-muted mb-0">{{ $models[$router->board_model]['label'] ?? 'Unknown Model' }} &middot; {{ $router->ip_address }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('routers.monitor', $router) }}" class="btn">
                    <i class="ti ti-activity icon"></i> Live Monitor
                </a>
                <button type="button" id="rp-test-conn-btn" class="btn btn-primary">
                    <i class="ti ti-broadcast" id="rp-test-conn-icon"></i>
                    <span id="rp-test-conn-label">Test Connection</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-center text-center">
                    <x-router-board
                        :port-count="$models[$router->board_model]['ports'] ?? 5"
                        :sfp-count="$models[$router->board_model]['sfp'] ?? 0"
                        :image="$models[$router->board_model]['image'] ?? null"
                        :status="$router->status"
                    />
                    <p class="text-muted small mt-3 mb-0">Uplink LED reflects live connection status</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title">Live System Info</h3>
                    <p class="text-muted" id="rp-live-loading">Checking connection...</p>

                    <div class="d-none flex-column flex-sm-row gap-4" id="rp-live-info">
                        <div class="row g-3 small flex-fill">
                            <div class="col-6">
                                <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Identity</p>
                                <p class="fw-bold mb-0" id="rp-live-identity">—</p>
                            </div>
                            <div class="col-6">
                                <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Detected Board</p>
                                <p class="fw-bold mb-0" id="rp-live-board">—</p>
                            </div>
                            <div class="col-6">
                                <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">RouterOS Version</p>
                                <p class="fw-bold mb-0" id="rp-live-version">—</p>
                            </div>
                            <div class="col-6">
                                <p class="text-uppercase text-muted mb-0" style="font-size:.625rem">Uptime</p>
                                <p class="fw-bold mb-0" id="rp-live-uptime">—</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start justify-content-center gap-4 flex-shrink-0">
                            <x-gauge-ring id="rp-gauge-cpu" color="var(--tblr-primary)" label="CPU Load" :size="84" />
                            <x-gauge-ring id="rp-gauge-mem" color="var(--tblr-success)" label="Memory Used" :size="84" detail="—" />
                        </div>
                    </div>

                    <div class="row g-3 mt-3 pt-3 border-top">
                        <div class="col-md-6">
                            <p class="text-uppercase text-muted mb-1" style="font-size:.625rem">Web Access</p>
                            @if($router->web_proxy_port)
                                <a href="http://{{ config('vpn.public_ip') }}:{{ $router->web_proxy_port }}" target="_blank" class="fw-bold d-inline-flex align-items-center gap-1">
                                    Open Web UI <i class="ti ti-external-link"></i>
                                </a>
                            @else
                                <span class="text-muted small">Not yet allocated — re-save this router to assign a proxy port.</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="text-uppercase text-muted mb-1" style="font-size:.625rem">Winbox Address</p>
                            @if($router->winbox_proxy_port)
                                <button type="button" id="rp-copy-winbox" class="btn btn-link p-0 fw-bold font-monospace" data-rp-copy="{{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }}">
                                    {{ config('vpn.public_ip') }}:{{ $router->winbox_proxy_port }} <i class="ti ti-copy"></i>
                                </button>
                                <p class="text-muted mb-0" style="font-size:.625rem">Paste this into Winbox's "Connect To" field — reachable from anywhere, not just this server.</p>
                            @else
                                <span class="text-muted small">Not yet allocated — re-save this router to assign a proxy port.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Captive Portal card hidden per request — form/routes/controller untouched, just not
         rendered here. Re-enable by un-commenting this block.
    <div id="captive-portal" class="card mb-3" style="scroll-margin-top:1.5rem;border-color:var(--tblr-primary)">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-world text-primary fs-3"></i>
                    <h3 class="card-title mb-0">Captive Portal</h3>
                    @if(! $captivePortal)
                        <span class="badge bg-amber-lt">Using default</span>
                    @endif
                </div>
                <a href="{{ route('captive.show', $router) }}" target="_blank" class="small d-inline-flex align-items-center gap-1">
                    Preview <i class="ti ti-external-link"></i>
                </a>
            </div>
            <p class="text-muted small mb-3">Customize the page hotspot customers see when they connect to this router's WiFi.</p>

            @php $oneispTemplate = old('template', $captivePortal->template ?? 'light-lumen'); @endphp
            <form action="{{ route('routers.captive-portal.update', $router) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Template</label>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-7 g-2">
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
                            <div class="col">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="template" value="{{ $key }}" class="form-selectgroup-input" @checked($oneispTemplate === $key)>
                                    <span class="form-selectgroup-label d-block text-center p-2">
                                        <span class="d-block rounded mb-2" style="height:1.75rem;{{ $opt['swatch'] }}"></span>
                                        <span class="small fw-bold">{{ $opt['label'] }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Logo URL <span class="text-muted text-lowercase">(optional)</span></label>
                        <input type="url" name="logo_url" value="{{ old('logo_url', $captivePortal->logo_url ?? '') }}" placeholder="https://..." class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand Color</label>
                        <input type="color" name="primary_color" value="{{ old('primary_color', $captivePortal->primary_color ?? '#2563eb') }}" class="form-control form-control-color w-100">
                    </div>
                </div>
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Plans Per Row</label>
                        @php $columnsPerRow = old('columns_per_row', $captivePortal->columns_per_row ?? ''); @endphp
                        <select name="columns_per_row" class="form-select">
                            <option value="" @selected($columnsPerRow === '')>Auto (responsive)</option>
                            @foreach([1, 2, 3, 4] as $n)
                                <option value="{{ $n }}" @selected((string) $columnsPerRow === (string) $n)>{{ $n }} per row</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-check">
                            <input type="checkbox" name="show_speed" value="1" @checked(old('show_speed', $captivePortal->show_speed ?? true)) class="form-check-input">
                            <span class="form-check-label">Show plan speeds</span>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-check">
                            <input type="checkbox" name="show_navbar" value="1" @checked(old('show_navbar', $captivePortal->show_navbar ?? false)) class="form-check-input">
                            <span class="form-check-label">Show top navbar</span>
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notice Title <span class="text-muted text-lowercase">(optional)</span></label>
                    <input type="text" name="notice_title" value="{{ old('notice_title', $captivePortal->notice_title ?? '') }}" placeholder="e.g. Scheduled maintenance" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notice Body <span class="text-muted text-lowercase">(optional)</span></label>
                    <textarea name="notice_body" rows="2" placeholder="Tell your customers what's happening..." class="form-control">{{ old('notice_body', $captivePortal->notice_body ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Testimonials <span class="text-muted text-lowercase">(optional — only shown if filled in, use real customer quotes)</span></label>
                    <div class="row g-3">
                        <div class="col-md-6 d-flex flex-column gap-2">
                            <input type="text" name="testimonial_1_text" value="{{ old('testimonial_1_text', $captivePortal->testimonial_1_text ?? '') }}" placeholder="&quot;Fast and reliable, never lets me down.&quot;" maxlength="255" class="form-control form-control-sm">
                            <input type="text" name="testimonial_1_author" value="{{ old('testimonial_1_author', $captivePortal->testimonial_1_author ?? '') }}" placeholder="— Jane, Eldoret" maxlength="100" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6 d-flex flex-column gap-2">
                            <input type="text" name="testimonial_2_text" value="{{ old('testimonial_2_text', $captivePortal->testimonial_2_text ?? '') }}" placeholder="&quot;Best hotspot in the estate.&quot;" maxlength="255" class="form-control form-control-sm">
                            <input type="text" name="testimonial_2_author" value="{{ old('testimonial_2_author', $captivePortal->testimonial_2_author ?? '') }}" placeholder="— David, Langas" maxlength="100" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Portal Settings</button>
            </form>

            <div class="mt-4 pt-4 border-top">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                        <p class="fw-bold mb-0">Sync Portal to Router</p>
                        <p class="text-muted small mt-1 mb-0">Pushes this portal to the router's hardware — run once, or again after a factory reset.</p>
                    </div>
                    <button type="button" id="rp-push-portal-btn" class="btn btn-sm flex-shrink-0" data-rp-push-url="{{ route('routers.captive-portal.push', $router) }}">
                        <i class="ti ti-upload" id="rp-push-portal-icon"></i>
                        <span id="rp-push-portal-label">Push Files to Router</span>
                    </button>
                </div>
                <p class="small fw-bold mt-2 mb-0 d-none" id="rp-push-portal-result"></p>
            </div>
        </div>
    </div>
    --}}

    <div class="card mb-3">
        <div class="card-body">
            <h3 class="card-title">Reprovision</h3>
            <p class="text-muted small mb-3">For a factory reset, a swapped-in replacement unit, or just re-syncing everything from scratch. Both are safe to run on an already-configured router — nothing here is destructive.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('routers.provision', $router) }}" class="btn">
                    <i class="ti ti-terminal-2 icon"></i> Re-run Bootstrap Script
                </a>
                <a href="{{ route('routers.ports', $router) }}" class="btn">
                    <i class="ti ti-topology-star icon"></i> Reconfigure Ports
                </a>
            </div>
            <p class="text-muted small mt-3 mb-0">Bootstrap script re-establishes the tunnel/RADIUS wiring (needs the router reachable to paste it into). Reconfigure Ports needs the router already online, and re-pushes hotspot/PPPoE/captive-portal/free-mode for whatever roles you assign.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title">Rename / Change Model</h3>
                    <form action="{{ route('routers.update', $router) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" required value="{{ $router->name }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Router Model</label>
                            <select name="board_model" required class="form-select">
                                @foreach($models as $key => $model)
                                    <option value="{{ $key }}" @selected($router->board_model === $key)>{{ $model['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div id="decommission" class="card h-100" style="border-color:var(--tblr-danger);scroll-margin-top:1.5rem">
                <div class="card-body">
                    <h3 class="card-title text-danger">Decommission Hardware</h3>
                    <p class="text-muted small mb-3">Permanently removes this router from your network topology. This cannot be undone.</p>

                    <div id="rp-decommission-idle">
                        <button type="button" id="rp-decommission-start-btn" class="btn btn-danger">
                            <i class="ti ti-trash" id="rp-decommission-start-icon"></i>
                            <span id="rp-decommission-start-label">Decommission</span>
                        </button>
                    </div>

                    <div id="rp-decommission-code" class="d-none" style="max-width:24rem">
                        <p class="mb-2">We sent a 6-digit code to your notifications (in-app + push). Enter it to confirm removal of <strong>{{ $router->name }}</strong>.</p>
                        <input type="text" id="rp-decommission-input" placeholder="000000" inputmode="numeric" maxlength="6" class="form-control text-center font-monospace fs-4 mb-2">
                        <p class="text-danger fw-bold d-none mb-2" id="rp-decommission-error"></p>
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" id="rp-decommission-confirm-btn" class="btn btn-danger" disabled>
                                <i class="ti ti-check" id="rp-decommission-confirm-icon"></i>
                                <span id="rp-decommission-confirm-label">Confirm Removal</span>
                            </button>
                            <button type="button" id="rp-decommission-cancel-btn" class="btn btn-link btn-sm">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            (function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // === Live system info / test connection ===
                var testBtn = document.getElementById('rp-test-conn-btn');
                var testIcon = document.getElementById('rp-test-conn-icon');
                var testLabel = document.getElementById('rp-test-conn-label');
                var loadingEl = document.getElementById('rp-live-loading');
                var infoEl = document.getElementById('rp-live-info');

                async function testConnection() {
                    testBtn.disabled = true;
                    testIcon.className = 'ti ti-loader-2 icon-spin';
                    testLabel.textContent = 'Testing...';

                    try {
                        var res = await fetch("{{ route('routers.test-connection', $router) }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        });
                        var data = await res.json();
                        var online = data.status === 'online';

                        document.getElementById('rp-live-identity').textContent = data.identity ?? '—';
                        document.getElementById('rp-live-board').textContent = data.board_model_detected ?? '—';
                        document.getElementById('rp-live-version').textContent = data.version ?? '—';
                        document.getElementById('rp-live-uptime').textContent = data.uptime ?? '—';

                        var cpuLoad = data.cpu_load ?? null;
                        document.getElementById('rp-gauge-cpu').style.background = 'conic-gradient(var(--tblr-primary) ' + ((cpuLoad ?? 0) * 3.6) + 'deg, var(--tblr-border-color) 0deg)';
                        document.getElementById('rp-gauge-cpu-value').textContent = cpuLoad !== null ? cpuLoad + '%' : '—';

                        var freeMemory = data.free_memory ?? null;
                        var totalMemory = data.total_memory ?? null;
                        var memPercent = freeMemory && totalMemory ? Math.round(((totalMemory - freeMemory) / totalMemory) * 100) : 0;
                        document.getElementById('rp-gauge-mem').style.background = 'conic-gradient(var(--tblr-success) ' + (memPercent * 3.6) + 'deg, var(--tblr-border-color) 0deg)';
                        document.getElementById('rp-gauge-mem-value').textContent = freeMemory && totalMemory ? memPercent + '%' : '—';
                        document.getElementById('rp-gauge-mem-detail').textContent = freeMemory && totalMemory
                            ? Math.round(freeMemory / 1048576) + ' MB / ' + Math.round(totalMemory / 1048576) + ' MB'
                            : '—';

                        loadingEl.style.display = 'none';
                        infoEl.classList.remove('d-none');
                        infoEl.classList.add('d-flex');

                        updateBoardLed(online);
                    } catch (e) {
                        updateBoardLed(false);
                    } finally {
                        testBtn.disabled = false;
                        testIcon.className = 'ti ti-broadcast';
                        testLabel.textContent = 'Test Connection';
                    }
                }

                function updateBoardLed(online) {
                    var led = document.querySelector('.router-board-led');
                    if (!led) return;
                    led.classList.remove('bg-green', 'bg-red', 'bg-amber');
                    if (online) {
                        led.classList.add('bg-green');
                        led.style.animation = 'router-board-blink 1s steps(1) infinite';
                    } else {
                        led.classList.add('bg-red');
                        led.style.animation = 'none';
                    }
                }

                testBtn.addEventListener('click', testConnection);
                testConnection();

                // === Copy winbox address ===
                var copyBtn = document.getElementById('rp-copy-winbox');
                if (copyBtn) {
                    copyBtn.addEventListener('click', function () {
                        navigator.clipboard.writeText(copyBtn.getAttribute('data-rp-copy'));
                        var icon = copyBtn.querySelector('i');
                        icon.className = 'ti ti-check text-success';
                        setTimeout(function () { icon.className = 'ti ti-copy'; }, 2000);
                    });
                }

                // === Push captive portal to router ===
                var pushBtn = document.getElementById('rp-push-portal-btn');
                var pushIcon = document.getElementById('rp-push-portal-icon');
                var pushLabel = document.getElementById('rp-push-portal-label');
                var pushResult = document.getElementById('rp-push-portal-result');

                pushBtn.addEventListener('click', async function () {
                    pushBtn.disabled = true;
                    pushIcon.className = 'ti ti-loader-2 icon-spin';
                    pushLabel.textContent = 'Pushing...';
                    pushResult.classList.add('d-none');

                    try {
                        var res = await fetch(pushBtn.getAttribute('data-rp-push-url'), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        });
                        var data = await res.json();
                        var ok = res.ok;
                        pushResult.textContent = ok
                            ? 'Walled garden: ' + data.walled_garden + '. Hotspot files pushed: ' + data.hotspot_files_pushed + '. Login method fixed on ' + data.login_by_fixed + ' profile(s). RADIUS address fixed on ' + data.radius_address_fixed + ' entr(ies). One-session-per-host fixed on ' + data.one_session_per_host_fixed + ' PPPoE server(s).'
                            : data.message;
                        pushResult.className = 'small fw-bold mt-2 mb-0 ' + (ok ? 'text-success' : 'text-danger');
                    } catch (e) {
                        pushResult.textContent = 'Network error — could not reach the router.';
                        pushResult.className = 'small fw-bold mt-2 mb-0 text-danger';
                    } finally {
                        pushBtn.disabled = false;
                        pushIcon.className = 'ti ti-upload';
                        pushLabel.textContent = 'Push Files to Router';
                    }
                });

                // === Decommission flow ===
                var idleWrap = document.getElementById('rp-decommission-idle');
                var codeWrap = document.getElementById('rp-decommission-code');
                var startBtn = document.getElementById('rp-decommission-start-btn');
                var startIcon = document.getElementById('rp-decommission-start-icon');
                var startLabel = document.getElementById('rp-decommission-start-label');
                var codeInput = document.getElementById('rp-decommission-input');
                var errorEl = document.getElementById('rp-decommission-error');
                var confirmBtn = document.getElementById('rp-decommission-confirm-btn');
                var confirmIcon = document.getElementById('rp-decommission-confirm-icon');
                var confirmLabel = document.getElementById('rp-decommission-confirm-label');
                var cancelBtn = document.getElementById('rp-decommission-cancel-btn');

                startBtn.addEventListener('click', async function () {
                    startBtn.disabled = true;
                    startIcon.className = 'ti ti-loader-2 icon-spin';
                    startLabel.textContent = 'Sending code...';

                    try {
                        var res = await fetch("{{ route('routers.decommission.request', $router) }}", {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        });
                        if (!res.ok) throw new Error();
                        idleWrap.classList.add('d-none');
                        codeWrap.classList.remove('d-none');
                    } catch (e) {
                        alert('Could not send a code. Please try again.');
                    } finally {
                        startBtn.disabled = false;
                        startIcon.className = 'ti ti-trash';
                        startLabel.textContent = 'Decommission';
                    }
                });

                codeInput.addEventListener('input', function () {
                    confirmBtn.disabled = codeInput.value.length !== 6;
                });

                cancelBtn.addEventListener('click', function () {
                    idleWrap.classList.remove('d-none');
                    codeWrap.classList.add('d-none');
                    codeInput.value = '';
                    errorEl.classList.add('d-none');
                });

                confirmBtn.addEventListener('click', async function () {
                    confirmBtn.disabled = true;
                    confirmIcon.className = 'ti ti-loader-2 icon-spin';
                    confirmLabel.textContent = 'Verifying...';
                    errorEl.classList.add('d-none');

                    try {
                        var res = await fetch("{{ route('routers.decommission.confirm', $router) }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                            body: JSON.stringify({ code: codeInput.value }),
                        });
                        var data = await res.json();
                        if (!res.ok) {
                            errorEl.textContent = data.message || 'That code is incorrect or has expired.';
                            errorEl.classList.remove('d-none');
                            confirmBtn.disabled = false;
                            confirmIcon.className = 'ti ti-check';
                            confirmLabel.textContent = 'Confirm Removal';
                            return;
                        }
                        window.location.href = data.redirect;
                    } catch (e) {
                        errorEl.textContent = 'Network error. Please try again.';
                        errorEl.classList.remove('d-none');
                        confirmBtn.disabled = false;
                        confirmIcon.className = 'ti ti-check';
                        confirmLabel.textContent = 'Confirm Removal';
                    }
                });
            })();
        </script>
    </x-slot>
</x-sidebar-layout>

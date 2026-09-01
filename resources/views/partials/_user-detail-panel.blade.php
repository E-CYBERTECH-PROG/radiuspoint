{{--
    Global slide-over user detail panel. Open it from anywhere with
    `window.rpOpenUserPanel(panelUrl)`.
--}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="rp-user-panel" aria-labelledby="rp-user-panel-title">
    <div class="offcanvas-header border-bottom">
        <div class="min-w-0">
            <p class="text-uppercase text-muted small fw-bold mb-0" id="rp-user-panel-type">Customer</p>
            <h3 class="offcanvas-title font-monospace text-truncate" id="rp-user-panel-title">—</h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="rp-user-panel-body">
        <div class="text-center text-muted py-5" id="rp-user-panel-loading" style="display:none">
            <i class="ti ti-loader-2 icon-spin icon icon-lg"></i>
        </div>
        <p class="text-danger" id="rp-user-panel-error" style="display:none"></p>
        <div id="rp-user-panel-content" style="display:none">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                <span class="badge" id="rp-panel-status"></span>
                <span class="badge bg-orange-lt" id="rp-panel-throttled" style="display:none"><i class="ti ti-gauge"></i> FUP Throttled</span>
                <span class="text-muted small" id="rp-panel-subtitle"></span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="text-uppercase text-muted small fw-bold">Router</div>
                    <div class="fw-bold" id="rp-panel-router">—</div>
                </div>
                <div class="col-6">
                    <div class="text-uppercase text-muted small fw-bold">Expiry</div>
                    <div class="fw-bold" id="rp-panel-expiry">—</div>
                    <div class="text-muted small" id="rp-panel-expiry-human"></div>
                </div>
                <div class="col-6" id="rp-panel-phone-wrap" style="display:none">
                    <div class="text-uppercase text-muted small fw-bold">Phone</div>
                    <div class="fw-bold" id="rp-panel-phone"></div>
                </div>
                <div class="col-6" id="rp-panel-mac-wrap" style="display:none">
                    <div class="text-uppercase text-muted small fw-bold">MAC</div>
                    <div class="fw-bold font-monospace small" id="rp-panel-mac"></div>
                </div>
            </div>

            <div class="mb-3" id="rp-panel-usage-wrap" style="display:none">
                <div class="text-uppercase text-muted small fw-bold mb-1">Data Usage This Cycle</div>
                <div class="font-monospace fw-bold" id="rp-panel-usage-text"></div>
                <div class="progress progress-sm mt-2" id="rp-panel-usage-bar-wrap" style="display:none">
                    <div class="progress-bar" id="rp-panel-usage-bar" role="progressbar"></div>
                </div>
                <div class="text-muted small mt-1">Since <span id="rp-panel-usage-since"></span></div>
            </div>

            <div class="border-top pt-3">
                <div class="mb-3">
                    <div class="text-uppercase text-muted small fw-bold mb-2">Extend</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm" data-extend-days="1">+1 Day</button>
                        <button type="button" class="btn btn-sm" data-extend-days="7">+7 Days</button>
                        <button type="button" class="btn btn-sm" data-extend-days="30">+30 Days</button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm" id="rp-panel-disconnect"><i class="ti ti-power"></i> Force Disconnect</button>
                    <button type="button" class="btn btn-sm" id="rp-panel-reset-mac" style="display:none"><i class="ti ti-refresh"></i> Reset MAC</button>
                </div>

                <p class="small fw-bold mt-2 mb-0" id="rp-panel-action-message" style="display:none"></p>
            </div>

            <a href="#" class="d-block text-center mt-3" id="rp-panel-edit-link">
                View Full Profile <i class="ti ti-arrow-right align-middle"></i>
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        var panelEl = document.getElementById('rp-user-panel');
        var offcanvas = null;
        var state = { panelUrl: null, data: null, acting: false };
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function getOffcanvas() {
            if (!offcanvas) offcanvas = new bootstrap.Offcanvas(panelEl);
            return offcanvas;
        }

        function setLoading(loading) {
            document.getElementById('rp-user-panel-loading').style.display = loading ? '' : 'none';
        }

        function setError(message) {
            var el = document.getElementById('rp-user-panel-error');
            el.textContent = message || '';
            el.style.display = message ? '' : 'none';
        }

        function statusClass(status) {
            if (status === 'active') return 'bg-green-lt';
            if (status === 'expired') return 'bg-amber-lt';
            if (status === 'offline') return 'bg-red-lt';
            return 'bg-secondary-lt';
        }

        function usageBarClass(percent) {
            if (percent >= 100) return 'bg-red';
            if (percent >= 80) return 'bg-amber';
            return 'bg-blue';
        }

        function render() {
            var data = state.data;
            var contentEl = document.getElementById('rp-user-panel-content');

            if (!data) {
                contentEl.style.display = 'none';
                return;
            }

            document.getElementById('rp-user-panel-type').textContent = data.type === 'pppoe' ? 'PPPoE Customer' : 'Hotspot Customer';
            document.getElementById('rp-user-panel-title').textContent = data.title || '—';

            var statusEl = document.getElementById('rp-panel-status');
            statusEl.textContent = data.status || '';
            statusEl.className = 'badge ' + statusClass(data.status);

            document.getElementById('rp-panel-throttled').style.display = data.usage && data.usage.throttled ? '' : 'none';
            document.getElementById('rp-panel-subtitle').textContent = data.subtitle || '';

            document.getElementById('rp-panel-router').textContent = data.router_name || '—';
            document.getElementById('rp-panel-expiry').textContent = data.expires_at || '—';
            document.getElementById('rp-panel-expiry-human').textContent = data.expires_human || '';

            document.getElementById('rp-panel-phone-wrap').style.display = data.phone_number ? '' : 'none';
            document.getElementById('rp-panel-phone').textContent = data.phone_number || '';

            document.getElementById('rp-panel-mac-wrap').style.display = data.mac_address ? '' : 'none';
            document.getElementById('rp-panel-mac').textContent = data.mac_address || '';

            var usageWrap = document.getElementById('rp-panel-usage-wrap');
            if (data.usage) {
                usageWrap.style.display = '';
                var text = data.usage.used_mb.toLocaleString() + ' MB';
                if (data.usage.cap_mb) text += ' / ' + data.usage.cap_mb.toLocaleString() + ' MB';
                document.getElementById('rp-panel-usage-text').textContent = text;
                document.getElementById('rp-panel-usage-since').textContent = data.usage.cycle_start;

                var barWrap = document.getElementById('rp-panel-usage-bar-wrap');
                if (data.usage.percent !== null && data.usage.percent !== undefined) {
                    barWrap.style.display = '';
                    var bar = document.getElementById('rp-panel-usage-bar');
                    bar.style.width = data.usage.percent + '%';
                    bar.className = 'progress-bar ' + usageBarClass(data.usage.percent);
                } else {
                    barWrap.style.display = 'none';
                }
            } else {
                usageWrap.style.display = 'none';
            }

            document.getElementById('rp-panel-reset-mac').style.display = data.type === 'hotspot' ? '' : 'none';
            document.getElementById('rp-panel-edit-link').href = data.edit_url || '#';

            contentEl.style.display = '';
        }

        async function open(panelUrl) {
            state.panelUrl = panelUrl;
            state.data = null;
            setError(null);
            setLoading(true);
            document.getElementById('rp-user-panel-content').style.display = 'none';
            document.getElementById('rp-panel-action-message').style.display = 'none';
            getOffcanvas().show();

            try {
                var res = await fetch(panelUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('failed');
                state.data = await res.json();
                render();
            } catch (e) {
                setError('Could not load customer details.');
            } finally {
                setLoading(false);
            }
        }

        async function refresh() {
            if (!state.panelUrl) return;
            try {
                var res = await fetch(state.panelUrl, { headers: { Accept: 'application/json' } });
                if (res.ok) {
                    state.data = await res.json();
                    render();
                }
            } catch (e) {
                // Keep showing the last-known data — the action's own message already reported success/failure.
            }
        }

        async function runAction(url, body) {
            if (!url || state.acting) return;
            state.acting = true;
            var msgEl = document.getElementById('rp-panel-action-message');

            try {
                var res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(body || {}),
                });
                var json = await res.json().catch(function () { return {}; });
                msgEl.textContent = json.message || (res.ok ? 'Done.' : 'Action failed.');
                msgEl.className = 'small fw-bold mt-2 mb-0 ' + (res.ok ? 'text-success' : 'text-danger');
                msgEl.style.display = '';
                if (res.ok) await refresh();
            } catch (e) {
                msgEl.textContent = 'Action failed — check your connection.';
                msgEl.className = 'small fw-bold mt-2 mb-0 text-danger';
                msgEl.style.display = '';
            } finally {
                state.acting = false;
            }
        }

        document.querySelectorAll('[data-extend-days]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                runAction(state.data && state.data.extend_url, { days: parseInt(btn.getAttribute('data-extend-days'), 10) });
            });
        });

        document.getElementById('rp-panel-disconnect').addEventListener('click', async function () {
            if (!(await window.rpConfirmAsync("Disconnect this customer's active session now?"))) return;
            runAction(state.data && state.data.disconnect_url);
        });

        document.getElementById('rp-panel-reset-mac').addEventListener('click', async function () {
            if (!(await window.rpConfirmAsync("Clear this customer's bound MAC address? The next device to connect will bind automatically."))) return;
            runAction(state.data && state.data.reset_mac_url);
        });

        window.rpOpenUserPanel = open;
    })();
</script>

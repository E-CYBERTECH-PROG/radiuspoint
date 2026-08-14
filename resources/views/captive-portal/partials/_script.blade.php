<script>
    const rpLinkLoginOnly = @json($linkLoginOnly);
    const rpAutoReconnect = @json($autoReconnect ?? null);

    const rpPortal = (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const linkLoginOnly = rpLinkLoginOnly;
        const payUrl = @json(route('portal.pay', $router));
        const statusUrlTemplate = @json(route('portal.status', [$router, '__ID__']));
        const lookupUrl = @json(route('captive.lookup', $router));
        const lookupReceiptUrl = @json(route('captive.lookup-receipt', $router));
        const freeModeUrl = @json(route('captive.free-mode', $router));
        const mac = @json($mac);

        let selectedPlanId = null;
        let pollTimer = null;

        function show(overlayId) {
            document.getElementById(overlayId).classList.add('open');
        }
        function close(overlayId) {
            document.getElementById(overlayId).classList.remove('open');
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }
        function step(prefix, name) {
            ['phone', 'message', 'code', 'confirm', 'waiting', 'success', 'failed', 'error'].forEach(s => {
                const el = document.getElementById(`rp-${prefix}-step-${s}`);
                if (el) el.style.display = (s === name) ? 'block' : 'none';
            });
        }

        function openBuy(planId, planName) {
            selectedPlanId = planId;
            document.getElementById('rp-buy-plan-label').textContent = `Paying for "${planName}" — enter the phone number to pay from.`;
            document.getElementById('rp-buy-error').style.display = 'none';
            document.getElementById('rp-buy-phone').value = '';
            step('buy', 'phone');
            show('rp-modal-buy');
        }

        function openReconnect() {
            document.getElementById('rp-reconnect-error').style.display = 'none';
            document.getElementById('rp-reconnect-phone').value = '';
            step('reconnect', 'phone');
            show('rp-modal-reconnect');
        }

        function openVoucher() {
            document.getElementById('rp-voucher-error').style.display = 'none';
            document.getElementById('rp-voucher-code').value = '';
            step('voucher', 'code');
            show('rp-modal-voucher');
        }

        function resetBuy() {
            step('buy', 'phone');
        }

        async function submitBuy() {
            const phone = document.getElementById('rp-buy-phone').value.trim();
            const errorEl = document.getElementById('rp-buy-error');
            const btn = document.getElementById('rp-buy-submit');

            if (!/^(?:254|0)7\d{8}$/.test(phone)) {
                errorEl.textContent = 'Enter a valid phone number (e.g. 0712345678).';
                errorEl.style.display = 'block';
                return;
            }

            errorEl.style.display = 'none';
            btn.disabled = true;

            try {
                const res = await fetch(payUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ plan_id: selectedPlanId, phone, mac }),
                });
                const data = await res.json();

                if (!res.ok) {
                    errorEl.textContent = data.error || 'Something went wrong. Please try again.';
                    errorEl.style.display = 'block';
                    btn.disabled = false;
                    return;
                }

                step('buy', 'waiting');
                pollBuyStatus(data.transaction_id);
            } catch (e) {
                errorEl.textContent = 'Network error. Please try again.';
                errorEl.style.display = 'block';
                btn.disabled = false;
            }
        }

        function pollBuyStatus(transactionId) {
            const url = statusUrlTemplate.replace('__ID__', transactionId);
            pollTimer = setInterval(async () => {
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    if (data.status === 'success') {
                        clearInterval(pollTimer); pollTimer = null;
                        document.getElementById('rp-buy-username').textContent = data.username;
                        document.getElementById('rp-buy-password').textContent = data.password;
                        document.getElementById('rp-buy-submit').disabled = false;
                        step('buy', 'success');
                    } else if (data.status === 'failed') {
                        clearInterval(pollTimer); pollTimer = null;
                        document.getElementById('rp-buy-fail-reason').textContent = 'The payment was not completed.';
                        document.getElementById('rp-buy-submit').disabled = false;
                        step('buy', 'failed');
                    }
                } catch (e) {
                    // Transient — next tick retries.
                }
            }, 3000);
        }

        async function submitReconnect() {
            const phone = document.getElementById('rp-reconnect-phone').value.trim();
            const errorEl = document.getElementById('rp-reconnect-error');
            const btn = document.getElementById('rp-reconnect-submit');

            if (!/^(?:254|0)7\d{8}$/.test(phone)) {
                errorEl.textContent = 'Enter a valid phone number (e.g. 0712345678).';
                errorEl.style.display = 'block';
                return;
            }

            errorEl.style.display = 'none';
            btn.disabled = true;

            try {
                const res = await fetch(lookupUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ phone }),
                });
                const data = await res.json();
                btn.disabled = false;

                if (data.found) {
                    document.getElementById('rp-reconnect-username').textContent = data.username;
                    document.getElementById('rp-reconnect-password').textContent = data.password;
                    step('reconnect', 'success');
                } else {
                    errorEl.textContent = data.message || 'No active plan found for this number.';
                    errorEl.style.display = 'block';
                }
            } catch (e) {
                btn.disabled = false;
                errorEl.textContent = 'Network error. Please try again.';
                errorEl.style.display = 'block';
            }
        }

        function openReceipt() {
            document.getElementById('rp-receipt-error').style.display = 'none';
            document.getElementById('rp-receipt-message').value = '';
            step('receipt', 'message');
            show('rp-modal-receipt');
        }

        async function submitReceipt() {
            const message = document.getElementById('rp-receipt-message').value.trim();
            const errorEl = document.getElementById('rp-receipt-error');
            const btn = document.getElementById('rp-receipt-submit');

            if (!message) {
                errorEl.textContent = 'Paste the M-Pesa confirmation message you received.';
                errorEl.style.display = 'block';
                return;
            }

            errorEl.style.display = 'none';
            btn.disabled = true;

            try {
                const res = await fetch(lookupReceiptUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ message }),
                });
                const data = await res.json();
                btn.disabled = false;

                if (data.found) {
                    document.getElementById('rp-receipt-username').textContent = data.username;
                    document.getElementById('rp-receipt-password').textContent = data.password;
                    step('receipt', 'success');
                } else {
                    errorEl.textContent = data.message || 'No matching payment found.';
                    errorEl.style.display = 'block';
                }
            } catch (e) {
                btn.disabled = false;
                errorEl.textContent = 'Network error. Please try again.';
                errorEl.style.display = 'block';
            }
        }

        function submitVoucher() {
            const code = document.getElementById('rp-voucher-code').value.trim().toUpperCase();
            const errorEl = document.getElementById('rp-voucher-error');

            if (!code) {
                errorEl.textContent = 'Enter your voucher code.';
                errorEl.style.display = 'block';
                return;
            }

            if (!linkLoginOnly) {
                errorEl.textContent = 'Could not detect the router login page — please enter the code manually as both username and password on your WiFi login screen.';
                errorEl.style.display = 'block';
                return;
            }

            // A voucher code IS the credential (username = password = code) — no server
            // round-trip needed, RouterOS/RADIUS validates it the same way any login does.
            document.getElementById('rp-login-username').value = code;
            document.getElementById('rp-login-password').value = code;
            const form = document.getElementById('rp-hotspot-login-form');
            form.action = linkLoginOnly;
            form.submit();
        }

        function openFreeMode() {
            step('freemode', 'confirm');
            show('rp-modal-freemode');
        }

        async function submitFreeMode() {
            step('freemode', 'waiting');

            if (!linkLoginOnly) {
                document.getElementById('rp-freemode-error').textContent = 'Could not detect the router login page — please try reconnecting to the WiFi.';
                step('freemode', 'error');
                return;
            }

            try {
                const res = await fetch(freeModeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!res.ok) {
                    document.getElementById('rp-freemode-error').textContent = data.message || 'Could not start Free Mode. Please try again.';
                    step('freemode', 'error');
                    return;
                }

                document.getElementById('rp-login-username').value = data.username;
                document.getElementById('rp-login-password').value = data.password;
                const form = document.getElementById('rp-hotspot-login-form');
                form.action = linkLoginOnly;
                form.submit();
            } catch (e) {
                document.getElementById('rp-freemode-error').textContent = 'Network error. Please try again.';
                step('freemode', 'error');
            }
        }

        function autoConnect(prefix) {
            if (!linkLoginOnly) {
                alert('Could not detect the router login page — please enter the details manually in your WiFi login screen.');
                return;
            }
            document.getElementById('rp-login-username').value = document.getElementById(`rp-${prefix}-username`).textContent;
            document.getElementById('rp-login-password').value = document.getElementById(`rp-${prefix}-password`).textContent;
            const form = document.getElementById('rp-hotspot-login-form');
            form.action = linkLoginOnly;
            form.submit();
        }

        return { openBuy, openReconnect, openVoucher, openReceipt, openFreeMode, submitBuy, submitReconnect, submitVoucher, submitReceipt, submitFreeMode, resetBuy, autoConnect, close };
    })();

    // Silent cross-router/cross-visit auto-reconnect: if the server already matched this
    // device's MAC to a still-active plan (see CaptivePortalController::show()), skip the plan
    // list entirely and log them straight back in — no click needed, covers both "moved to a
    // different router" and "left and came back".
    if (rpAutoReconnect && rpLinkLoginOnly) {
        document.getElementById('rp-autoreconnect-overlay').style.display = 'flex';
        document.getElementById('rp-login-username').value = rpAutoReconnect.username;
        document.getElementById('rp-login-password').value = rpAutoReconnect.password;
        const form = document.getElementById('rp-hotspot-login-form');
        form.action = rpLinkLoginOnly;
        form.submit();
    }
</script>

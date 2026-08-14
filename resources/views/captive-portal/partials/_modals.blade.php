{{-- Buy modal: phone entry -> STK push -> poll -> success --}}
<div class="modal-overlay" id="rp-modal-buy">
    <div class="modal">
        <div id="rp-buy-step-phone">
            <h2>Pay with M-Pesa</h2>
            <p class="sub" id="rp-buy-plan-label">Enter the phone number to pay from.</p>
            <input type="tel" id="rp-buy-phone" placeholder="0712345678" inputmode="tel">
            <p class="error-text" id="rp-buy-error" style="display:none"></p>
            <button type="button" class="btn btn-brand btn-block" id="rp-buy-submit" onclick="rpPortal.submitBuy()">Pay Now</button>
            <button type="button" class="close-link" onclick="rpPortal.close('rp-modal-buy')">Cancel</button>
        </div>
        <div id="rp-buy-step-waiting" style="display:none">
            <svg class="icon spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#e5e7eb" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="var(--brand)" stroke-width="3" stroke-linecap="round"/></svg>
            <h2>Check your phone</h2>
            <p class="sub">Enter your M-Pesa PIN to complete the payment. This closes automatically once it's done.</p>
        </div>
        <div id="rp-buy-step-success" style="display:none">
            <svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#16a34a"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <h2>You're connected!</h2>
            <p class="sub">Save these in case you need to log in manually.</p>
            <div class="creds">
                <div class="k">Username</div>
                <div class="v" id="rp-buy-username"></div>
                <div class="k">Password</div>
                <div class="v" id="rp-buy-password"></div>
            </div>
            <button type="button" class="btn btn-brand btn-block" onclick="rpPortal.autoConnect('buy')">Connect Me Automatically</button>
        </div>
        <div id="rp-buy-step-failed" style="display:none">
            <svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#dc2626"/><path d="M9 9l6 6M15 9l-6 6" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
            <h2>Payment failed</h2>
            <p class="sub" id="rp-buy-fail-reason">The payment wasn't completed.</p>
            <button type="button" class="btn btn-brand btn-block" onclick="rpPortal.resetBuy()">Try Again</button>
        </div>
    </div>
</div>

{{-- Reconnect modal: phone lookup -> success --}}
<div class="modal-overlay" id="rp-modal-reconnect">
    <div class="modal">
        <div id="rp-reconnect-step-phone">
            <h2>Reconnect</h2>
            <p class="sub">Enter the phone number you paid with.</p>
            <input type="tel" id="rp-reconnect-phone" placeholder="0712345678" inputmode="tel">
            <p class="error-text" id="rp-reconnect-error" style="display:none"></p>
            <button type="button" class="btn btn-brand btn-block" id="rp-reconnect-submit" onclick="rpPortal.submitReconnect()">Find My Plan</button>
            <button type="button" class="close-link" onclick="rpPortal.close('rp-modal-reconnect')">Cancel</button>
        </div>
        <div id="rp-reconnect-step-success" style="display:none">
            <svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#16a34a"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <h2>Plan found!</h2>
            <p class="sub">Save these in case you need to log in manually.</p>
            <div class="creds">
                <div class="k">Username</div>
                <div class="v" id="rp-reconnect-username"></div>
                <div class="k">Password</div>
                <div class="v" id="rp-reconnect-password"></div>
            </div>
            <button type="button" class="btn btn-brand btn-block" onclick="rpPortal.autoConnect('reconnect')">Connect Me Automatically</button>
        </div>
    </div>
</div>

{{-- Voucher modal: a voucher code IS the login credential (username = password = code, see
     VoucherController::generate) — no lookup needed, just submit it straight to the router. --}}
<div class="modal-overlay" id="rp-modal-voucher">
    <div class="modal">
        <div id="rp-voucher-step-code">
            <h2>Enter Voucher Code</h2>
            <p class="sub">Type the code printed on your voucher.</p>
            <input type="text" id="rp-voucher-code" placeholder="e.g. A1B2C3D4" style="text-transform:uppercase">
            <p class="error-text" id="rp-voucher-error" style="display:none"></p>
            <button type="button" class="btn btn-brand btn-block" onclick="rpPortal.submitVoucher()">Connect</button>
            <button type="button" class="close-link" onclick="rpPortal.close('rp-modal-voucher')">Cancel</button>
        </div>
    </div>
</div>

{{-- Free Mode: connects instantly on a throttled, WhatsApp/Facebook-only session — no phone
     number or payment needed. See RouterController::provisionFreeMode() for the router-side
     enforcement and CaptivePortalController::freeMode() for the credential. --}}
<div class="modal-overlay" id="rp-modal-freemode">
    <div class="modal">
        <div id="rp-freemode-step-confirm">
            <svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#16a34a"/><path d="M12 7v5l3 3" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <h2>Free Mode</h2>
            <p class="sub">Free access to WhatsApp &amp; Facebook messaging for 30 minutes. Text and photos work — video calls and streaming won't, since the connection is intentionally slow. When your 30 minutes are up you'll land back here to buy a full-speed plan.</p>
            <button type="button" class="btn btn-brand btn-block" onclick="rpPortal.submitFreeMode()">Start Free Mode</button>
            <button type="button" class="close-link" onclick="rpPortal.close('rp-modal-freemode')">Cancel</button>
        </div>
        <div id="rp-freemode-step-waiting" style="display:none">
            <svg class="icon spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#e5e7eb" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="var(--brand)" stroke-width="3" stroke-linecap="round"/></svg>
            <h2>Connecting…</h2>
        </div>
        <div id="rp-freemode-step-error" style="display:none">
            <p class="error-text" id="rp-freemode-error"></p>
            <button type="button" class="btn btn-brand btn-block" onclick="rpPortal.close('rp-modal-freemode')">Close</button>
        </div>
    </div>
</div>

<form id="rp-hotspot-login-form" method="post" style="display:none">
    <input type="hidden" name="username" id="rp-login-username">
    <input type="hidden" name="password" id="rp-login-password">
</form>

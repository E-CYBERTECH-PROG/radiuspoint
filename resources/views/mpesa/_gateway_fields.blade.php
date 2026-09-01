{{-- $prefix: 'primary' or 'backup'; $setting: the MpesaSetting model for that slot --}}
<div class="row g-3" data-rp-gateway-group="{{ $prefix }}">
    <div class="col-md-6">
        <label class="form-label">Gateway Type @if($prefix === 'primary')<span class="text-danger">*</span>@endif</label>
        <select name="{{ $prefix }}_gateway_type" id="{{ $prefix }}_gateway_type" data-rp-gateway-select {{ $prefix === 'primary' ? 'required' : '' }} class="form-select">
            <option value="till" @selected(old("{$prefix}_gateway_type", $setting->gateway_type) === 'till')>Till (Buy Goods)</option>
            <option value="paybill" @selected(old("{$prefix}_gateway_type", $setting->gateway_type) === 'paybill')>Paybill</option>
            <option value="bank" @selected(old("{$prefix}_gateway_type", $setting->gateway_type) === 'bank')>Bank</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Environment @if($prefix === 'primary')<span class="text-danger">*</span>@endif</label>
        <select name="{{ $prefix }}_environment" {{ $prefix === 'primary' ? 'required' : '' }} class="form-select">
            <option value="sandbox" @selected(old("{$prefix}_environment", $setting->environment ?? 'sandbox') === 'sandbox')>Sandbox (Testing)</option>
            <option value="production" @selected(old("{$prefix}_environment", $setting->environment) === 'production')>Production (Live)</option>
        </select>
    </div>

    <div class="col-md-6" data-rp-gateway-fields="till">
        <label class="form-label">Till Number</label>
        <input type="text" name="{{ $prefix }}_shortcode" value="{{ old("{$prefix}_shortcode", $setting->shortcode) }}" placeholder="174379" class="form-control">
        <p class="text-muted small mt-1">Where your customers' payments land — this doesn't have to be a shortcode you have Daraja API access to.</p>
    </div>

    <div class="col-md-6" data-rp-gateway-fields="paybill">
        <label class="form-label">Paybill Number</label>
        <input type="text" name="{{ $prefix }}_shortcode" value="{{ old("{$prefix}_shortcode", $setting->shortcode) }}" placeholder="174379" class="form-control">
        <p class="text-muted small mt-1">Where your customers' payments land — this doesn't have to be a shortcode you have Daraja API access to.</p>
    </div>

    <div class="col-md-6" data-rp-gateway-fields="paybill">
        <label class="form-label">Default Account Number</label>
        <input type="text" name="{{ $prefix }}_account_number" value="{{ old("{$prefix}_account_number", $setting->account_number) }}" placeholder="Optional" class="form-control">
        <p class="text-muted small mt-1">Used only if a payment has no customer-specific reference of its own.</p>
    </div>

    <div class="col-md-6" data-rp-gateway-fields="bank">
        <label class="form-label">Bank Paybill Number</label>
        <input type="text" name="{{ $prefix }}_bank_paybill" value="{{ old("{$prefix}_bank_paybill", $setting->bank_paybill) }}" placeholder="e.g. 247247" class="form-control">
        <p class="text-muted small mt-1">Your bank's own M-Pesa paybill number, e.g. Equity/KCB.</p>
    </div>

    <div class="col-md-6" data-rp-gateway-fields="bank">
        <label class="form-label">Bank Account Number</label>
        <input type="text" name="{{ $prefix }}_bank_account_number" value="{{ old("{$prefix}_bank_account_number", $setting->bank_account_number) }}" placeholder="Your bank account number" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-check">
            <input type="checkbox" name="{{ $prefix }}_is_active" value="1" id="{{ $prefix }}_is_active" @checked(old("{$prefix}_is_active", $setting->is_active)) class="form-check-input">
            <span class="form-check-label fw-bold">Gateway Active</span>
        </label>
    </div>

    <div class="col-12 mt-2 pt-3 border-top">
        <p class="text-uppercase text-muted small fw-bold mb-1">Your own Daraja app (optional)</p>
        <p class="text-muted small mb-3">Leave these blank and RadiusPoint's shared M-Pesa gateway will initiate the STK push on your behalf — the money still lands on the till/paybill/bank above. Only fill these in if you have your own Safaricom Daraja API app for this shortcode.</p>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Consumer Key</label>
                <input type="password" name="{{ $prefix }}_consumer_key" placeholder="{{ $setting->exists && $setting->consumer_key ? '•••• saved' : '' }}" class="form-control">
                <p class="text-muted small mt-1">Leave blank to keep the saved value.</p>
            </div>

            <div class="col-md-4">
                <label class="form-label">Consumer Secret</label>
                <input type="password" name="{{ $prefix }}_consumer_secret" placeholder="{{ $setting->exists && $setting->consumer_secret ? '•••• saved' : '' }}" class="form-control">
                <p class="text-muted small mt-1">Leave blank to keep the saved value.</p>
            </div>

            <div class="col-md-4">
                <label class="form-label">Passkey</label>
                <input type="password" name="{{ $prefix }}_passkey" placeholder="{{ $setting->exists && $setting->passkey ? '•••• saved' : '' }}" class="form-control">
                <p class="text-muted small mt-1">Leave blank to keep the saved value.</p>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-gateway-select]').forEach(function (select) {
                var group = select.closest('[data-rp-gateway-group]');

                function sync() {
                    group.querySelectorAll('[data-rp-gateway-fields]').forEach(function (el) {
                        el.style.display = el.getAttribute('data-rp-gateway-fields') === select.value ? '' : 'none';
                    });
                }

                select.addEventListener('change', sync);
                sync();
            });
        });
    </script>
@endonce

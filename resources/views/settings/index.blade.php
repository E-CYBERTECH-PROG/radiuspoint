{{-- Expects $tab, $user, $tenant, $mpesaPrimary, $mpesaBackup, $smsSetting, $timezones, $currencies. --}}
<x-sidebar-layout title="Account">
    <div class="mb-4">
        <h1 class="mb-1">Account</h1>
        <p class="text-muted mb-0">Manage your profile, billing plan, and integrations.</p>
    </div>

    @php
        $navItem = fn ($key, $icon, $label) => [$key, $icon, $label];
        $tabs = [
            $navItem('general', 'ti-user-circle', 'General'),
            $navItem('licence', 'ti-shield-check', 'Licence'),
        ];
        if (Auth::user()->role !== 'Sales Agent') {
            $tabs[] = $navItem('payment-gateway', 'ti-credit-card', 'Payment Gateway');
            $tabs[] = $navItem('sms-gateway', 'ti-message-dots', 'SMS Gateway');
        }
        $tabs[] = $navItem('email-gateway', 'ti-mail', 'Email Gateway');
        $tabs[] = $navItem('notes-template', 'ti-file-text', 'Notes Template');
        $tabs[] = $navItem('change-password', 'ti-lock', 'Change Password');
        $tabs[] = $navItem('2fa', 'ti-shield-lock', 'Enable 2FA');
    @endphp

    <div class="row">
        <div class="col-12 col-lg-3">
            <div class="card">
                <div class="list-group list-group-flush nav flex-column" role="tablist">
                    @foreach($tabs as [$key, $icon, $label])
                        <a href="#rp-tab-{{ $key }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $tab === $key ? 'active' : '' }}" data-bs-toggle="pill" role="tab">
                            <i class="ti {{ $icon }}"></i> {{ $label }}
                        </a>
                    @endforeach
                    <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 text-muted">
                        <i class="ti ti-palette"></i> Appearance &amp; Danger Zone
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">
            <div class="tab-content">

                {{-- ---------- GENERAL ---------- --}}
                <div class="tab-pane {{ $tab === 'general' ? 'active show' : '' }}" id="rp-tab-general" role="tabpanel">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h2 class="card-title">General</h2>
                            <p class="text-muted small mb-4">Your personal details and business identity.</p>

                            <form action="{{ route('account.update-general') }}" method="POST">
                                @csrf @method('PUT')
                                @php [$firstName, $lastName] = array_pad(explode(' ', $user->name, 2), 2, ''); @endphp
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" required value="{{ old('first_name', $firstName) }}" class="form-control">
                                        @error('first_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" required value="{{ old('last_name', $lastName) }}" class="form-control">
                                        @error('last_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Business Name</label>
                                        <input type="text" name="company_name" required value="{{ old('company_name', $tenant->company_name) }}" class="form-control">
                                        @error('company_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ISP Prefix</label>
                                        <input type="text" name="isp_prefix" value="{{ old('isp_prefix', $tenant->isp_prefix) }}" placeholder="e.g. ACME" class="form-control">
                                        @error('isp_prefix') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sub-Domain Name</label>
                                        <input type="text" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" placeholder="acme" class="form-control">
                                        @error('subdomain') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="form-control">
                                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                                        @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Regional / captive-portal-facing details — still lives here, just reusing company-settings' own endpoint. --}}
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Regional &amp; Support</h2>
                            <p class="text-muted small mb-4">Shown on your captive portals, plus timezone and currency for reports.</p>
                            <form action="{{ route('company-settings.update') }}" method="POST">
                                @csrf @method('PUT')
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Support Phone</label>
                                        <input type="text" name="support_phone" value="{{ old('support_phone', $tenant->support_phone) }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" value="{{ old('location', $tenant->location) }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Timezone</label>
                                        <select name="timezone" required class="form-select">
                                            @foreach($timezones as $value => $label)
                                                <option value="{{ $value }}" @selected(old('timezone', $tenant->timezone) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Currency</label>
                                        <select name="currency_symbol" required class="form-select">
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency }}" @selected(old('currency_symbol', $tenant->currency_symbol) === $currency)>{{ $currency }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ---------- LICENCE ---------- --}}
                <div class="tab-pane {{ $tab === 'licence' ? 'active show' : '' }}" id="rp-tab-licence" role="tabpanel">
                    @php
                        $licenceColor = [
                            'active' => 'green',
                            'trial' => 'primary',
                            'expired' => 'red',
                            'cancelled' => 'secondary',
                        ][$tenant->subscription_status] ?? 'secondary';
                    @endphp
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div>
                                    <p class="text-uppercase text-muted small fw-bold mb-1">Current Plan</p>
                                    <h2 class="mb-0">{{ ucfirst($tenant->subscription_tier) }}</h2>
                                </div>
                                <span class="badge bg-{{ $licenceColor }}-lt text-uppercase">{{ $tenant->subscription_status }}</span>
                            </div>
                            <div class="row g-3 py-3 border-top border-bottom">
                                <div class="col-sm-6">
                                    <p class="text-uppercase text-muted small fw-bold mb-1">Renews / Expires</p>
                                    <p class="fw-bold mb-0">{{ $tenant->subscription_expires_at?->format('d M Y') ?? 'No expiry set' }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="text-uppercase text-muted small fw-bold mb-1">Account Status</p>
                                    <p class="fw-bold mb-0">{{ ucfirst($tenant->status) }}</p>
                                </div>
                            </div>
                            <div class="mt-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
                                <p class="text-muted mb-0">Commission invoices, payment history, and plan changes are managed under Billing.</p>
                                <a href="{{ route('billing.edit') }}" class="btn btn-primary flex-shrink-0">View Billing</a>
                            </div>
                        </div>
                    </div>
                </div>

                @if(Auth::user()->role !== 'Sales Agent')
                {{-- ---------- PAYMENT GATEWAY ---------- --}}
                <div class="tab-pane {{ $tab === 'payment-gateway' ? 'active show' : '' }}" id="rp-tab-payment-gateway" role="tabpanel">
                    <form action="{{ route('mpesa-settings.update') }}" method="POST">
                        @csrf @method('PUT')

                        <div id="rp-primary-view" class="mb-3" @if(!$mpesaPrimary->exists) style="display:none" @endif>
                            <x-mpesa-gateway-card :setting="$mpesaPrimary" label="Primary" icon="ti-credit-card" color="blue" data-rp-edit-toggle="rp-primary" style="cursor:pointer" />
                        </div>
                        <div id="rp-primary-edit" class="card mb-3" @if($mpesaPrimary->exists) style="display:none" @endif>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h2 class="card-title d-flex align-items-center gap-2 mb-1"><i class="ti ti-credit-card text-primary"></i> Primary Gateway</h2>
                                        <p class="text-muted small mb-0">Every payment is attempted here first.</p>
                                    </div>
                                    @if($mpesaPrimary->exists)
                                        <button type="button" class="btn btn-link btn-sm" data-rp-edit-toggle="rp-primary">Cancel</button>
                                    @endif
                                </div>
                                @include('mpesa._gateway_fields', ['prefix' => 'primary', 'setting' => $mpesaPrimary])
                            </div>
                        </div>

                        <div id="rp-backup-view" class="mb-3" @if(!$mpesaBackup->exists) style="display:none" @endif>
                            <x-mpesa-gateway-card :setting="$mpesaBackup" label="Backup" icon="ti-shield-check" color="amber" data-rp-edit-toggle="rp-backup" style="cursor:pointer" />
                        </div>
                        <div id="rp-backup-edit" class="card mb-3" @if($mpesaBackup->exists) style="display:none" @endif>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h2 class="card-title d-flex align-items-center gap-2 mb-1"><i class="ti ti-shield-check text-warning"></i> Backup Gateway <span class="fw-normal text-muted text-lowercase small">(optional)</span></h2>
                                        <p class="text-muted small mb-0">Steps in automatically if the primary fails.</p>
                                    </div>
                                    @if($mpesaBackup->exists)
                                        <button type="button" class="btn btn-link btn-sm" data-rp-edit-toggle="rp-backup">Cancel</button>
                                    @endif
                                </div>
                                @include('mpesa._gateway_fields', ['prefix' => 'backup', 'setting' => $mpesaBackup])
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>

                {{-- ---------- SMS GATEWAY ---------- --}}
                <div class="tab-pane {{ $tab === 'sms-gateway' ? 'active show' : '' }}" id="rp-tab-sms-gateway" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted small mb-4">Messages are currently log-only until a provider is connected.</p>
                            <form action="{{ route('sms-settings.update') }}" method="POST">
                                @csrf @method('PUT')
                                <div class="mb-3">
                                    <label class="form-label">Provider</label>
                                    <input type="text" name="provider" value="{{ old('provider', $smsSetting->provider) }}" placeholder="e.g., Africa's Talking" class="form-control">
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Sender ID</label>
                                        <input type="text" name="sender_id" value="{{ old('sender_id', $smsSetting->sender_id) }}" placeholder="RADIUSPOINT" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">API Username</label>
                                        <input type="text" name="username" value="{{ old('username', $smsSetting->username) }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">API Key</label>
                                        <input type="password" name="api_key" placeholder="{{ $smsSetting->exists && $smsSetting->api_key ? '•••• saved' : '' }}" class="form-control">
                                        <p class="text-muted small mt-1 mb-0">Leave blank to keep the saved value.</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">API Secret</label>
                                        <input type="password" name="api_secret" placeholder="{{ $smsSetting->exists && $smsSetting->api_secret ? '•••• saved' : '' }}" class="form-control">
                                        <p class="text-muted small mt-1 mb-0">Leave blank to keep the saved value.</p>
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ---------- INERT / COMING SOON TABS ---------- --}}
                @foreach([
                    'email-gateway' => ['ti-mail', 'Email Gateway', 'Send transactional emails through your own SMTP provider.'],
                    'notes-template' => ['ti-file-text', 'Notes Template', 'Reusable note templates for invoices and customer records.'],
                    '2fa' => ['ti-shield-lock', 'Two-Factor Authentication', 'Add an extra verification step when logging in.'],
                ] as $key => [$icon, $label, $desc])
                    <div class="tab-pane {{ $tab === $key ? 'active show' : '' }}" id="rp-tab-{{ $key }}" role="tabpanel">
                        <div class="card">
                            <div class="card-body py-5 d-flex flex-column align-items-center justify-content-center text-center">
                                <i class="ti {{ $icon }} icon icon-lg text-muted mb-2"></i>
                                <h2 class="card-title mb-1">{{ $label }}</h2>
                                <p class="text-muted small" style="max-width:20rem">{{ $desc }}</p>
                                <span title="Coming soon" class="badge bg-secondary-lt mt-2">
                                    <i class="ti ti-clock"></i> Coming Soon
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- ---------- CHANGE PASSWORD ---------- --}}
                <div class="tab-pane {{ $tab === 'change-password' ? 'active show' : '' }}" id="rp-tab-change-password" role="tabpanel">
                    <div class="card" style="max-width:36rem">
                        <div class="card-body">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-edit-toggle]').forEach(function (el) {
                el.addEventListener('click', function () {
                    var group = el.getAttribute('data-rp-edit-toggle');
                    var view = document.getElementById(group + '-view');
                    var edit = document.getElementById(group + '-edit');
                    var editing = edit.style.display !== 'none';
                    view.style.display = editing ? '' : 'none';
                    edit.style.display = editing ? 'none' : '';
                });
            });
        });
    </script>
</x-sidebar-layout>

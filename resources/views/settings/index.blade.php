{{-- Expects $tab, $user, $tenant, $mpesaPrimary, $mpesaBackup, $smsSetting, $timezones, $currencies. --}}
<x-sidebar-layout title="Account">
    <div x-data="{ tab: '{{ $tab }}' }">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Account</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your profile, billing plan, and integrations.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-6 items-start">
            {{-- === TAB LIST === --}}
            <div class="w-full lg:w-64 shrink-0 bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-2">
                @php
                    $navItem = fn ($key, $icon, $label) => [$key, $icon, $label];
                    $tabs = [
                        $navItem('general', 'bx-user-circle', 'General'),
                        $navItem('licence', 'bx-shield-quarter', 'Licence'),
                    ];
                    if (Auth::user()->role !== 'Sales Agent') {
                        $tabs[] = $navItem('payment-gateway', 'bx-credit-card', 'Payment Gateway');
                        $tabs[] = $navItem('sms-gateway', 'bx-message-square-dots', 'SMS Gateway');
                    }
                    $tabs[] = $navItem('email-gateway', 'bx-envelope', 'Email Gateway');
                    $tabs[] = $navItem('notes-template', 'bx-file', 'Notes Template');
                    $tabs[] = $navItem('change-password', 'bx-lock-alt', 'Change Password');
                    $tabs[] = $navItem('2fa', 'bx-shield', 'Enable 2FA');
                @endphp
                @foreach($tabs as [$key, $icon, $label])
                    <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-left">
                        <i class="bx {{ $icon }} text-lg shrink-0"></i>
                        <span>{{ $label }}</span>
                    </button>
                @endforeach
                <div class="border-t border-gray-100 dark:border-gray-800 my-2"></div>
                <a href="{{ route('profile.edit') }}" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <i class="bx bx-palette text-lg shrink-0"></i>
                    <span>Appearance &amp; Danger Zone</span>
                </a>
            </div>

            {{-- === TAB PANELS === --}}
            <div class="flex-1 w-full min-w-0">

                {{-- ---------- GENERAL ---------- --}}
                <div x-show="tab === 'general'" x-cloak class="space-y-6">
                    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
                        <h2 class="text-md font-bold text-gray-900 dark:text-white mb-1">General</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Your personal details and business identity.</p>

                        <form action="{{ route('account.update-general') }}" method="POST" class="space-y-5">
                            @csrf @method('PUT')
                            @php [$firstName, $lastName] = array_pad(explode(' ', $user->name, 2), 2, ''); @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">First Name</label>
                                    <input type="text" name="first_name" required value="{{ old('first_name', $firstName) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('first_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Last Name</label>
                                    <input type="text" name="last_name" required value="{{ old('last_name', $lastName) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('last_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Business Name</label>
                                    <input type="text" name="company_name" required value="{{ old('company_name', $tenant->company_name) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('company_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">ISP Prefix</label>
                                    <input type="text" name="isp_prefix" value="{{ old('isp_prefix', $tenant->isp_prefix) }}" placeholder="e.g. ACME" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('isp_prefix') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Sub-Domain Name</label>
                                    <input type="text" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" placeholder="acme" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('subdomain') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email</label>
                                    <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Changes</button>
                            </div>
                        </form>
                    </div>

                    {{-- Regional / captive-portal-facing details — still lives here, just reusing company-settings' own endpoint. --}}
                    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
                        <h2 class="text-md font-bold text-gray-900 dark:text-white mb-1">Regional &amp; Support</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Shown on your captive portals, plus timezone and currency for reports.</p>
                        <form action="{{ route('company-settings.update') }}" method="POST" class="space-y-5">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Support Phone</label>
                                    <input type="text" name="support_phone" value="{{ old('support_phone', $tenant->support_phone) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Location</label>
                                    <input type="text" name="location" value="{{ old('location', $tenant->location) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Timezone</label>
                                    <select name="timezone" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                        @foreach($timezones as $value => $label)
                                            <option value="{{ $value }}" @selected(old('timezone', $tenant->timezone) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Currency</label>
                                    <select name="currency_symbol" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency }}" @selected(old('currency_symbol', $tenant->currency_symbol) === $currency)>{{ $currency }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ---------- LICENCE ---------- --}}
                <div x-show="tab === 'licence'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
                    @php
                        $licenceStyle = [
                            'active' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50',
                            'trial' => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-900/50',
                            'expired' => 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900/50',
                            'cancelled' => 'bg-gray-50 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-800',
                        ][$tenant->subscription_status] ?? 'bg-gray-50 dark:bg-gray-900/40 text-gray-500 border-gray-200';
                    @endphp
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Current Plan</p>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ ucfirst($tenant->subscription_tier) }}</h2>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide px-3 py-1.5 rounded-full border {{ $licenceStyle }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $tenant->subscription_status }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-t border-b border-gray-100 dark:border-gray-800">
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Renews / Expires</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tenant->subscription_expires_at?->format('d M Y') ?? 'No expiry set' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Account Status</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ ucfirst($tenant->status) }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Commission invoices, payment history, and plan changes are managed under Billing.</p>
                        <a href="{{ route('billing.edit') }}" class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg transition-colors">View Billing</a>
                    </div>
                </div>

                @if(Auth::user()->role !== 'Sales Agent')
                {{-- ---------- PAYMENT GATEWAY ---------- --}}
                <div x-show="tab === 'payment-gateway'" x-cloak>
                    <div x-data="{ primaryEditing: {{ $mpesaPrimary->exists ? 'false' : 'true' }}, backupEditing: {{ $mpesaBackup->exists ? 'false' : 'true' }} }">
                        <form action="{{ route('mpesa-settings.update') }}" method="POST" class="space-y-5">
                            @csrf @method('PUT')

                            <div x-show="!primaryEditing" x-cloak>
                                <x-mpesa-gateway-card :setting="$mpesaPrimary" label="Primary" icon="bx-credit-card" color="blue" @click="primaryEditing = true" />
                            </div>
                            <div x-show="primaryEditing" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8 space-y-8">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="bx bx-credit-card text-indigo-500"></i> Primary Gateway</h2>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Every payment is attempted here first.</p>
                                    </div>
                                    @if($mpesaPrimary->exists)
                                        <button type="button" @click="primaryEditing = false" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Cancel</button>
                                    @endif
                                </div>
                                @include('mpesa._gateway_fields', ['prefix' => 'primary', 'setting' => $mpesaPrimary])
                            </div>

                            <div x-show="!backupEditing" x-cloak>
                                <x-mpesa-gateway-card :setting="$mpesaBackup" label="Backup" icon="bx-shield-quarter" color="amber" @click="backupEditing = true" />
                            </div>
                            <div x-show="backupEditing" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8 space-y-8">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="bx bx-shield-quarter text-amber-500"></i> Backup Gateway <span class="text-xs font-normal text-gray-400 normal-case">(optional)</span></h2>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Steps in automatically if the primary fails.</p>
                                    </div>
                                    @if($mpesaBackup->exists)
                                        <button type="button" @click="backupEditing = false" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Cancel</button>
                                    @endif
                                </div>
                                @include('mpesa._gateway_fields', ['prefix' => 'backup', 'setting' => $mpesaBackup])
                            </div>

                            <div class="flex justify-end pt-1">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ---------- SMS GATEWAY ---------- --}}
                <div x-show="tab === 'sms-gateway'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Messages are currently log-only until a provider is connected.</p>
                    <form action="{{ route('sms-settings.update') }}" method="POST" class="space-y-5">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Provider</label>
                            <input type="text" name="provider" value="{{ old('provider', $smsSetting->provider) }}" placeholder="e.g., Africa's Talking" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Sender ID</label>
                                <input type="text" name="sender_id" value="{{ old('sender_id', $smsSetting->sender_id) }}" placeholder="RADIUSPOINT" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">API Username</label>
                                <input type="text" name="username" value="{{ old('username', $smsSetting->username) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">API Key</label>
                                <input type="password" name="api_key" placeholder="{{ $smsSetting->exists && $smsSetting->api_key ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">API Secret</label>
                                <input type="password" name="api_secret" placeholder="{{ $smsSetting->exists && $smsSetting->api_secret ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Settings</button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- ---------- INERT / COMING SOON TABS ---------- --}}
                @foreach([
                    'email-gateway' => ['bx-envelope', 'Email Gateway', 'Send transactional emails through your own SMTP provider.'],
                    'notes-template' => ['bx-file', 'Notes Template', 'Reusable note templates for invoices and customer records.'],
                    '2fa' => ['bx-shield', 'Two-Factor Authentication', 'Add an extra verification step when logging in.'],
                ] as $key => [$icon, $label, $desc])
                    <div x-show="tab === '{{ $key }}'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-12 flex flex-col items-center justify-center text-center">
                        <i class="bx {{ $icon }} text-4xl text-gray-300 dark:text-gray-700 mb-3"></i>
                        <h2 class="text-md font-bold text-gray-900 dark:text-white mb-1">{{ $label }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 max-w-xs">{{ $desc }}</p>
                        <span title="Coming soon" class="mt-4 inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-full cursor-not-allowed select-none">
                            <i class="bx bx-time-five"></i> Coming Soon
                        </span>
                    </div>
                @endforeach

                {{-- ---------- CHANGE PASSWORD ---------- --}}
                <div x-show="tab === 'change-password'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8 max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>

            </div>
        </div>
    </div>
</x-sidebar-layout>

<x-sidebar-layout title="SMS Outbox">
    @php $oneispSmsTab = in_array(request('tab'), ['settings', 'templates', 'automation']) ? request('tab') : 'outbox'; @endphp

    <div class="mb-4">
        <span class="badge bg-secondary-lt">
            <i class="ti ti-info-circle"></i> Log Mode — No Gateway Connected
        </span>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a href="#rp-sms-outbox" class="nav-link {{ $oneispSmsTab === 'outbox' ? 'active' : '' }}" data-bs-toggle="pill">Outbox</a></li>
        <li class="nav-item"><a href="#rp-sms-templates" class="nav-link {{ $oneispSmsTab === 'templates' ? 'active' : '' }}" data-bs-toggle="pill">Templates</a></li>
        <li class="nav-item"><a href="#rp-sms-settings" class="nav-link {{ $oneispSmsTab === 'settings' ? 'active' : '' }}" data-bs-toggle="pill">Settings</a></li>
        <li class="nav-item"><a href="#rp-sms-automation" class="nav-link {{ $oneispSmsTab === 'automation' ? 'active' : '' }}" data-bs-toggle="pill">Automation</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane {{ $oneispSmsTab === 'outbox' ? 'active show' : '' }}" id="rp-sms-outbox">
            <form method="GET">
            <input type="hidden" name="tab" value="outbox">
            <div class="card">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Show</span>
                        <x-per-page-select />
                        <span class="text-muted small">Entries</span>
                        <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-sms" title="Filters">
                            <i class="ti ti-filter icon"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone number…" class="form-control">
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-compose-sms">
                            <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">Compose SMS</span>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap">
                        <thead>
                            <tr>
                                <th>Phone</th>
                                <th>Message</th>
                                <th>Initiator</th>
                                <th>Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $msg)
                                <tr>
                                    <td class="font-monospace">{{ $msg->phone_number }}</td>
                                    <td class="text-muted text-truncate" style="max-width:24rem">{{ $msg->message }}</td>
                                    <td class="text-muted">{{ $msg->initiator ?: 'System' }}</td>
                                    <td class="text-muted">{{ $msg->created_at->format('d M Y H:i') }}</td>
                                    <td class="text-center">
                                        <x-status-badge :color="$msg->status === 'sent' ? 'green' : ($msg->status === 'failed' ? 'red' : 'amber')">
                                            {{ $msg->status }}
                                        </x-status-badge>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('sms.destroy', $msg) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this message from the log?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-message-dots fs-1"></i></span>
                                        <p class="text-uppercase text-muted small mb-0">No messages sent yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $messages->links('vendor.pagination.rp-circles') }}</div>
            </div>

            <x-filter-modal name="sms" :clear-url="route('sms.index', ['tab' => 'outbox'])">
                <div class="col-12">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['queued', 'sent', 'failed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filter-modal>
            </form>
        </div>

        <div class="tab-pane {{ $oneispSmsTab === 'templates' ? 'active show' : '' }}" id="rp-sms-templates">
            <div class="card">
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                        @forelse($templates as $template)
                            <div class="col">
                                <div class="border rounded p-3" data-rp-template-card>
                                    <div class="d-flex justify-content-between gap-3" data-rp-template-view>
                                        <div>
                                            <p class="fw-bold small mb-1">{{ $template->name }}</p>
                                            <p class="text-muted small mb-0">{{ $template->body }}</p>
                                        </div>
                                        <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                            <button type="button" class="text-muted" style="background:none;border:0" data-rp-template-edit-toggle><i class="ti ti-edit"></i></button>
                                            <form action="{{ route('sms-templates.destroy', $template) }}" method="POST" onsubmit="return rpConfirm(event, 'Delete this template?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-danger" style="background:none;border:0"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <form action="{{ route('sms-templates.update', $template) }}" method="POST" class="d-none" data-rp-template-edit-form>
                                        @csrf @method('PUT')
                                        <input type="text" name="name" required value="{{ $template->name }}" class="form-control form-control-sm fw-bold mb-2">
                                        <textarea name="body" required rows="2" class="form-control form-control-sm mb-2">{{ $template->body }}</textarea>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                            <button type="button" class="btn btn-link btn-sm" data-rp-template-edit-toggle>Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-5">
                                <i class="ti ti-file-text icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No templates yet.</p>
                            </div>
                        @endforelse
                    </div>
                    <form action="{{ route('sms-templates.store') }}" method="POST" class="border-top pt-4 d-flex flex-column flex-md-row gap-2">
                        @csrf
                        <input type="text" name="name" required placeholder="Template name" class="form-control" style="max-width:14rem">
                        <input type="text" name="body" required placeholder="Message body" class="form-control flex-fill">
                        <button type="submit" class="btn btn-primary">Add Template</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane {{ $oneispSmsTab === 'settings' ? 'active show' : '' }}" id="rp-sms-settings">
            <div class="card" style="max-width:36rem">
                <div class="card-body">
                    <p class="text-muted small mb-4">Messages are currently log-only — no provider is connected yet.</p>
                    <form action="{{ route('sms-settings.update') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Provider</label>
                            <input type="text" name="provider" value="{{ old('provider', $setting->provider) }}" placeholder="e.g., Africa's Talking" class="form-control">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sender ID</label>
                                <input type="text" name="sender_id" value="{{ old('sender_id', $setting->sender_id) }}" placeholder="RADIUSPOINT" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">API Username</label>
                                <input type="text" name="username" value="{{ old('username', $setting->username) }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" placeholder="{{ $setting->exists && $setting->api_key ? '•••• saved' : '' }}" class="form-control">
                                <p class="text-muted small mt-1 mb-0">Leave blank to keep the saved value.</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">API Secret</label>
                                <input type="password" name="api_secret" placeholder="{{ $setting->exists && $setting->api_secret ? '•••• saved' : '' }}" class="form-control">
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

        <div class="tab-pane {{ $oneispSmsTab === 'automation' ? 'active show' : '' }}" id="rp-sms-automation">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted small mb-4">Send a template automatically on a customer event. Placeholders: <code class="small">{name} {plan} {expires_at} {code} {password}</code></p>
                    <form action="{{ route('sms-triggers.update') }}" method="POST">
                        @csrf @method('PUT')
                        @php
                            $triggerLabels = [
                                'pppoe_expiry_reminder_3d' => ['PPPoE — Expiry Reminder (3 days before)', 'Sent once, 3 days before a PPPoE account expires.'],
                                'pppoe_expired' => ['PPPoE — Account Expired', 'Sent the moment a PPPoE account is flipped to expired.'],
                                'pppoe_renewed' => ['PPPoE — Renewal Confirmed', 'Sent whenever a PPPoE account is extended/renewed.'],
                                'pppoe_welcome' => ['PPPoE — Welcome Message', 'Sent when a new active PPPoE customer is added.'],
                                'pppoe_payment_receipt' => ['PPPoE — Payment Receipt', 'Reserved for a future online PPPoE payment flow — not fired yet.'],
                                'hotspot_purchase_confirmed' => ['Hotspot — Purchase Confirmed', 'Sent after a successful M-Pesa hotspot purchase. Replaces the built-in login-details message when enabled.'],
                                'hotspot_voucher_created' => ['Hotspot — Voucher Created', 'Sent when a voucher is generated with a phone number attached. Replaces the built-in voucher-code message when enabled.'],
                            ];
                        @endphp
                        <div>
                            @foreach($triggerLabels as $key => [$label, $help])
                                @php $trigger = $triggers[$key] ?? null; @endphp
                                <div class="py-3 border-bottom d-flex flex-column flex-md-row align-items-md-center gap-3">
                                    <div style="flex:0 0 18rem">
                                        <label class="form-check">
                                            <input type="checkbox" name="triggers[{{ $key }}][enabled]" value="1" @checked($trigger?->enabled) class="form-check-input">
                                            <span class="form-check-label fw-bold">{{ $label }}</span>
                                        </label>
                                        <p class="text-muted small mt-1 mb-0 ms-4">{{ $help }}</p>
                                    </div>
                                    <select name="triggers[{{ $key }}][sms_template_id]" class="form-select flex-fill">
                                        <option value="">— No template selected —</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}" @selected($trigger?->sms_template_id === $template->id)>{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary">Save Automation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-compose-sms" @if($errors->sms->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Compose SMS</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('sms.store') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone_number" required value="{{ old('phone_number') }}" placeholder="0712345678" class="form-control">
                    @error('phone_number', 'sms') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                @if($templates->count())
                    <div class="mb-3">
                        <label class="form-label">Use Template <span class="text-muted text-lowercase">(optional)</span></label>
                        <select id="rp-sms-template-select" class="form-select">
                            <option value="">— None —</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea name="message" id="rp-sms-message" required rows="5" class="form-control"></textarea>
                    @error('message', 'sms') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Queue Message</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });

            document.querySelectorAll('[data-rp-template-card]').forEach(function (card) {
                var view = card.querySelector('[data-rp-template-view]');
                var form = card.querySelector('[data-rp-template-edit-form]');
                card.querySelectorAll('[data-rp-template-edit-toggle]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        view.classList.toggle('d-none');
                        form.classList.toggle('d-none');
                    });
                });
            });

            var templateSelect = document.getElementById('rp-sms-template-select');
            if (templateSelect) {
                var templateBodies = @json($templates->pluck('body', 'id'));
                templateSelect.addEventListener('change', function () {
                    if (templateSelect.value) document.getElementById('rp-sms-message').value = templateBodies[templateSelect.value];
                });
            }
        });
    </script>
</x-sidebar-layout>

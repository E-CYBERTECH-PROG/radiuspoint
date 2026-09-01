{{-- Full customer profile page — replaces the old global slide-over panel for the Customers
     hub. $user/$usage/$transactions/$totalSpent/$connectionLogs are all real, server-side
     data (see CustomerController::show()). A few tiles here (Wallet, Deposit, Request
     Payment, Adjust Balance, Resolve Payment, CPE Configuration, Update Group Policy,
     Override Package) have no backing feature in this app yet and are shown inert/disabled
     rather than faked — same honesty pattern as "Coming soon" in the sidebar. --}}
@php
    $oneispIsHotspot = $type === 'hotspot';
    $oneispIdentifier = $oneispIsHotspot ? $user->phone_number : $user->username;
    $oneispRadiusUsername = $oneispIdentifier;
    $oneispInitials = strtoupper(substr($user->name ?: $oneispIdentifier, 0, 2));

    $oneispUpdateUrl = $oneispIsHotspot ? route('hotspot-users.update', $user) : route('pppoe-users.update', $user);
    $oneispDisconnectUrl = $oneispIsHotspot ? route('hotspot-users.disconnect', $user) : route('pppoe-users.disconnect', $user);
    $oneispIsDisabled = $user->status === 'offline';
    $oneispDisableUrl = $oneispIsDisabled
        ? ($oneispIsHotspot ? route('hotspot-users.enable', $user) : route('pppoe-users.enable', $user))
        : ($oneispIsHotspot ? route('hotspot-users.disable', $user) : route('pppoe-users.disable', $user));
    $oneispDestroyUrl = $oneispIsHotspot ? route('hotspot-users.destroy', $user) : route('pppoe-users.destroy', $user);
    $oneispExtendUrl = $oneispIsHotspot ? route('hotspot-users.extend', $user) : route('pppoe-users.extend', $user);

    // "offline" is what the Disable button actually sets (see disable()) — that's this app's
    // closest equivalent to "disabled". "unused" (hotspot-only: a voucher not yet redeemed)
    // is the closest equivalent to "paused". Expiry is checked against the real clock, not
    // just the stored status, since a customer can still say 'active' for a little while
    // after expires_at passes, before the expiry sweep catches up.
    $oneispPlanState = match (true) {
        $user->status === 'offline' => 'disabled',
        $user->status === 'unused' => 'paused',
        $user->status === 'expired' || ($user->expires_at && $user->expires_at->isPast()) => 'expired',
        default => 'active',
    };
    $oneispPlanColor = [
        'active' => 'primary',
        'expired' => 'red',
        'disabled' => 'secondary',
        'paused' => 'amber',
    ][$oneispPlanState];
@endphp
<x-sidebar-layout title="Customer Details">
    <div class="mb-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <h1 class="font-monospace mb-0">{{ $oneispIdentifier }}</h1>
        <div class="d-flex align-items-center gap-2">
            <button type="button" disabled title="No CPE provisioning integration yet" class="btn btn-primary">
                CPE Configuration
            </button>
            <div class="dropdown">
                <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown">
                    Actions
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <button type="button" class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#rp-edit-offcanvas"><i class="ti ti-edit icon"></i> Edit Full Profile</button>
                    <form action="{{ $oneispDisconnectUrl }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="ti ti-power icon"></i> Force Disconnect</button>
                    </form>
                    <div class="dropdown-divider"></div>
                    <form action="{{ $oneispDestroyUrl }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this customer permanently? This also removes their RADIUS credentials.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ti ti-trash icon"></i> Delete Customer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- === IDENTITY + PLAN === --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-9">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3 mb-4">
                                <span class="avatar avatar-xl bg-orange-lt font-monospace fw-bold flex-shrink-0">{{ $oneispInitials }}</span>
                                <div class="flex-fill min-w-0">
                                    <p class="fw-bold text-uppercase mb-2 text-truncate">{{ $user->name ?: '—' }}</p>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#rp-edit-offcanvas">View Info</button>
                                        <form action="{{ $oneispDisableUrl }}" method="POST" class="flex-fill" onsubmit="return rpConfirm(event, '{{ $oneispIsDisabled ? 'Enable' : 'Disable' }} this customer?')">
                                            @csrf
                                            @if($oneispIsDisabled)
                                                <button type="submit" class="btn btn-outline-success btn-sm w-100">Enable</button>
                                            @else
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Disable</button>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-4 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar bg-primary-lt"><i class="ti ti-wallet"></i></span>
                                    <div>
                                        <p class="font-monospace fw-bold mb-0">KES 0</p>
                                        <p class="text-muted mb-0" style="font-size:.625rem">Wallet Balance</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar bg-green-lt"><i class="ti ti-trending-up"></i></span>
                                    <div>
                                        <p class="font-monospace fw-bold mb-0">KES {{ number_format($totalSpent) }}</p>
                                        <p class="text-muted mb-0" style="font-size:.625rem">Total Spent</p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" disabled title="No payment-request feature yet" class="btn btn-outline-success btn-sm">Request Payment</button>
                                <button type="button" disabled title="No wallet feature yet" class="btn btn-warning btn-sm">Adjust Balance</button>
                            </div>
                        </div>

                        <div class="col-md-6 border-start-md ps-md-4">
                            @foreach([
                                ['icon' => 'ti-user', 'label' => 'Username', 'value' => $oneispIdentifier],
                                ['icon' => 'ti-calendar', 'label' => 'Registered', 'value' => $user->created_at->format('H:i M d, Y')],
                                ['icon' => 'ti-activity', 'label' => 'Status', 'value' => ucfirst($user->status)],
                                ['icon' => 'ti-router', 'label' => 'Type', 'value' => ucfirst($type)],
                                ['icon' => 'ti-map-pin', 'label' => 'Location', 'value' => $user->address ?: '—'],
                                ['icon' => 'ti-phone', 'label' => 'Contact', 'value' => $user->phone_number ?: '—'],
                            ] as $row)
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="d-flex align-items-center gap-2 text-muted flex-shrink-0" style="width:7rem">
                                        <i class="ti {{ $row['icon'] }}"></i>
                                        <span class="small">{{ $row['label'] }}</span>
                                    </span>
                                    <span class="fw-bold text-truncate">{{ $row['value'] }}</span>
                                </div>
                            @endforeach

                            <button type="button" disabled title="No group policy feature yet" class="btn w-100 mt-2">
                                Update Group Policy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-uppercase text-{{ $oneispPlanColor }} small fw-bold mb-1">Current Plan</p>
                    <p class="fw-bold mb-3">{{ $user->plan?->name ?? '— No plan —' }}</p>

                    <p class="text-uppercase text-{{ $oneispPlanColor }} small fw-bold mb-1">Monthly Usage</p>
                    <p class="font-monospace fw-bold mb-3">
                        @if($usage)
                            {{ number_format($usage['used_mb']) }} MB @if($usage['cap_mb']) / {{ number_format($usage['cap_mb']) }} MB @endif
                        @else
                            —
                        @endif
                    </p>

                    <p class="text-uppercase text-{{ $oneispPlanColor }} small fw-bold mb-1">Expiry Date</p>
                    <p class="font-monospace fw-bold mb-4">{{ $user->expires_at?->format('H:i M d, Y') ?? '—' }}</p>

                    <button type="button" class="btn btn-{{ $oneispPlanColor }} w-100 mb-2" data-bs-toggle="offcanvas" data-bs-target="#rp-edit-offcanvas">Change Plan</button>
                    <button type="button" disabled title="No package-override feature yet" class="btn btn-outline-{{ $oneispPlanColor }} w-100">Override Package</button>
                </div>
            </div>
        </div>
    </div>

    {{-- === CHILD ACCOUNT + QUICK ACTIONS === --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title">User Connection Logs</h3>

                    @if($connectionLogs->isEmpty())
                        <p class="text-muted text-uppercase small py-3">No connection logs yet.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($connectionLogs as $log)
                                <li class="d-flex gap-2 mb-3">
                                    <span class="rounded-circle bg-primary flex-shrink-0 mt-1" style="width:.625rem;height:.625rem"></span>
                                    <div class="flex-fill">
                                        <div class="d-flex align-items-start justify-content-between gap-3">
                                            <p class="fw-bold mb-0">Access-Accept</p>
                                            <p class="text-muted small flex-shrink-0 mb-0">{{ \Carbon\Carbon::parse($log->acctstarttime)->format('H:i d/m/Y') }}</p>
                                        </div>
                                        <p class="text-muted small mb-0">Status: UserOk Password: {{ $oneispRadiusUsername }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="row row-cols-3 g-3">
                <div class="col">
                    <button type="button" class="btn btn-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4" data-bs-toggle="offcanvas" data-bs-target="#rp-password-offcanvas" @if($oneispIsHotspot) disabled title="Not applicable for Hotspot" @endif>
                        <i class="ti ti-lock fs-3"></i> <span class="small">Change Password</span>
                    </button>
                </div>
                <div class="col">
                    <button type="button" class="btn btn-danger w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4" data-bs-toggle="offcanvas" data-bs-target="#rp-expiry-offcanvas">
                        <i class="ti ti-calendar-cog fs-3"></i> <span class="small">Change Expiry</span>
                    </button>
                </div>
                <div class="col">
                    @if($oneispIsHotspot)
                        <form action="{{ route('hotspot-users.reset-mac', $user) }}" method="POST" class="h-100" onsubmit="return rpConfirm(event, 'Clear this customer\'s bound MAC address?')">
                            @csrf
                            <button type="submit" class="btn btn-info w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4">
                                <i class="ti ti-refresh fs-3"></i> <span class="small">Reset Site/MAC</span>
                            </button>
                        </form>
                    @else
                        <button type="button" disabled title="Not applicable for PPPoE" class="btn btn-info w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4">
                            <i class="ti ti-refresh fs-3"></i> <span class="small">Reset Site/MAC</span>
                        </button>
                    @endif
                </div>
                <div class="col">
                    <a href="{{ route('sms.index') }}" class="btn btn-orange w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4">
                        <i class="ti ti-message-dots fs-3"></i> <span class="small">Send SMS</span>
                    </a>
                </div>
                <div class="col">
                    <button type="button" disabled title="No wallet feature yet" class="btn btn-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4">
                        <i class="ti ti-credit-card fs-3"></i> <span class="small">Deposit</span>
                    </button>
                </div>
                <div class="col">
                    <button type="button" disabled title="No payment-resolution feature yet" class="btn btn-success w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-1 py-4">
                        <i class="ti ti-checks fs-3"></i> <span class="small">Resolve Payment</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- === TRANSACTIONS === --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item"><a href="#rp-tx-mpesa" class="nav-link active" data-bs-toggle="pill">M-Pesa</a></li>
                <li class="nav-item"><a href="#rp-tx-invoice" class="nav-link" data-bs-toggle="pill">Invoice</a></li>
                <li class="nav-item"><a href="#rp-tx-account" class="nav-link" data-bs-toggle="pill">Account Transactions</a></li>
            </ul>
            <div class="input-icon" style="width:14rem">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" id="rp-tx-search" placeholder="Search transactions…" class="form-control form-control-sm">
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane" id="rp-tx-invoice" role="tabpanel">
                <p class="text-center text-muted text-uppercase small py-5 mb-0">No invoices for this customer.</p>
            </div>

            <div class="tab-pane active show" id="rp-tx-mpesa" role="tabpanel">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Phone Number</th>
                                <th>Created On</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                <tr data-rp-tx-search="{{ strtolower(($tx->mpesa_receipt ?? '').' '.$tx->phone_number) }}">
                                    <td class="text-muted">{{ $tx->id }}</td>
                                    <td class="text-muted font-monospace">{{ $tx->mpesa_receipt ?: 'N/A' }}</td>
                                    <td class="fw-bold">{{ number_format($tx->amount) }}</td>
                                    <td class="text-muted">{{ $tx->payment_method }}</td>
                                    <td class="text-muted">{{ $tx->phone_number }}</td>
                                    <td class="text-muted">{{ $tx->created_at->format('H:i M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted text-uppercase small py-5">No transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane" id="rp-tx-account" role="tabpanel">
                <p class="text-center text-muted text-uppercase small py-5 mb-0">No account transactions.</p>
            </div>
        </div>
    </div>

    {{-- === CHANGE EXPIRY OFFCANVAS === --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-expiry-offcanvas">
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Change Expiry</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ $oneispExtendUrl }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Extend by (days)</label>
                    <input type="number" name="days" min="1" max="365" placeholder="7" class="form-control">
                </div>
                <p class="text-muted small text-center">— or —</p>
                <div class="mb-3">
                    <label class="form-label">Set exact expiry</label>
                    <input type="datetime-local" name="expires_at" class="form-control">
                </div>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-danger w-100">Save</button>
            </div>
        </form>
    </div>

    {{-- === CHANGE PASSWORD OFFCANVAS (PPPoE only) === --}}
    @unless($oneispIsHotspot)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-password-offcanvas">
            <div class="offcanvas-header border-bottom">
                <h3 class="offcanvas-title">Change Password</h3>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <form action="{{ route('pppoe-users.change-password', $user) }}" method="POST" class="d-flex flex-column h-100">
                @csrf
                <div class="offcanvas-body">
                    <label class="form-label">New RADIUS Password</label>
                    <input type="text" name="password" required minlength="4" class="form-control">
                </div>
                <div class="offcanvas-footer p-3 border-top">
                    <button type="submit" class="btn btn-primary w-100">Save</button>
                </div>
            </form>
        </div>
    @endunless

    {{-- === VIEW / EDIT INFO OFFCANVAS — same field set as the Customers hub's "Add Customer"
         offcanvas, pre-filled and editable. Status/expiry/router are preserved server-side
         (see PppoeUserController::update()/HotspotUserController::update()) since those
         are changed via their own dedicated actions above, not this form. === --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-edit-offcanvas" style="--tblr-offcanvas-width:42rem" @if($errors->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Customer Info</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <form action="{{ $oneispUpdateUrl }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

            <div class="offcanvas-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Connection Type</label>
                        <div>
                            <span class="badge bg-primary-lt">
                                <i class="ti {{ $oneispIsHotspot ? 'ti-broadcast' : 'ti-device-desktop' }}"></i> {{ $oneispIsHotspot ? 'Hotspot' : 'PPPoE' }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" placeholder="John" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" placeholder="Doe" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="john@example.com" class="form-control">
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number @unless($oneispIsHotspot)<span class="text-muted text-lowercase">(optional)</span>@else<span class="text-danger">*</span>@endunless</label>
                        <input type="tel" name="phone_number" @if($oneispIsHotspot) required @endif value="{{ old('phone_number', $user->phone_number) }}" placeholder="0712345678" class="form-control">
                        @error('phone_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}" placeholder="Street, town" class="form-control">
                    </div>

                    @if($oneispIsHotspot)
                        <div class="col-12">
                            <label class="form-label">MAC Address</label>
                            <input type="text" name="mac_address" value="{{ old('mac_address', $user->mac_address) }}" placeholder="AA:BB:CC:DD:EE:FF" class="form-control font-monospace">
                        </div>
                    @else
                        <div class="col-12">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" required value="{{ old('username', $user->username) }}" placeholder="e.g., johndoe" class="form-control font-monospace">
                            @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label">Package</label>
                        <select name="current_plan_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('current_plan_id', $user->current_plan_id) == $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-device-floppy icon"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });

            var searchInput = document.getElementById('rp-tx-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    var q = searchInput.value.toLowerCase();
                    document.querySelectorAll('[data-rp-tx-search]').forEach(function (row) {
                        row.style.display = !q || row.getAttribute('data-rp-tx-search').includes(q) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</x-sidebar-layout>

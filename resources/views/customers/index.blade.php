{{-- Expects $users (paginated PppoeUser|HotspotUser), $plans (current tab's Plan list, for
     the filter bar), $allPlans (all plans keyed by id, for row display), $pppoePlans/
     $hotspotPlans (for the Add Customer offcanvas' Package dropdown), $routers (keyed by id),
     $tab ('pppoe'|'hotspot'), $stats (scoped to $tab only) in scope. --}}
<x-sidebar-layout title="Customers">
    {{-- === STAT TILES === --}}
    @php
        $oneispCustomerStatTiles = [
            ['label' => 'Total Users', 'value' => $stats['total'], 'icon' => 'ti-user', 'bg' => 'bg-primary-lt'],
            ['label' => 'Active Users', 'value' => $stats['active'], 'icon' => 'ti-user-check', 'bg' => 'bg-green-lt'],
            ['label' => 'Expired Users', 'value' => $stats['expired'], 'icon' => 'ti-user-x', 'bg' => 'bg-red-lt'],
            ['label' => 'Disabled Users', 'value' => $stats['disabled'], 'icon' => 'ti-user-minus', 'bg' => 'bg-secondary-lt'],
        ];
    @endphp
    <div class="card mb-3" style="border-radius:.5rem">
        <div class="d-flex flex-column flex-sm-row rp-stat-strip">
            @foreach($oneispCustomerStatTiles as $tile)
                <div class="flex-fill p-3">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fs-2 font-monospace fw-bold mb-0">{{ number_format($tile['value']) }}</p>
                            <p class="text-muted small mt-1 mb-0">{{ $tile['label'] }}</p>
                        </div>
                        <span class="avatar {{ $tile['bg'] }} flex-shrink-0"><i class="ti {{ $tile['icon'] }} fs-3"></i></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- === LIVE ONLINE/OFFLINE FILTER — updated as the poll() below refreshes each row's
         live status; purely client-side, doesn't touch the DB `status` column (that's the
         billing status, filterable separately via the Filters offcanvas). === --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <button type="button" id="rp-live-filter-online" data-rp-live-filter="online" class="nav-link">
                Online (<span id="rp-live-count-online">0</span>)
            </button>
        </li>
        <li class="nav-item">
            <button type="button" id="rp-live-filter-offline" data-rp-live-filter="offline" class="nav-link">
                Offline (<span id="rp-live-count-offline">0</span>)
            </button>
        </li>
    </ul>

    {{-- === TOOLBAR + FILTERS + TABLE (one card) === --}}
    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-customers" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by username, name, or phone…" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-customer">
                        <i class="ti ti-user-plus icon"></i> <span class="d-none d-sm-inline">New Customer</span>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>Plan</th>
                            <th>Online</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $oneispKey = $tab === 'hotspot' ? $user->phone_number : $user->username;
                                $oneispDetailUrl = route('customers.show', ['type' => $tab, 'token' => \App\Http\Controllers\CustomerController::tokenFor($tab, $user->id)]);
                            @endphp
                            <tr data-live-row="{{ $oneispKey }}">
                                <td class="font-monospace">
                                    <a href="{{ $oneispDetailUrl }}" class="fw-bold">
                                        {{ $tab === 'hotspot' ? $user->phone_number : $user->username }}
                                    </a>
                                </td>
                                <td class="text-muted">{{ $user->name ?: '—' }}</td>
                                <td class="text-muted">{{ $user->phone_number ?: '—' }}</td>
                                <td class="text-muted">{{ $allPlans[$user->current_plan_id]->name ?? '—' }}</td>
                                <td data-live-cell="{{ $oneispKey }}">
                                    <span class="text-muted">·</span>
                                </td>
                                <td>
                                    @if($user->status === 'active')
                                        <x-status-badge color="green">active</x-status-badge>
                                    @elseif($user->status === 'expired')
                                        <x-status-badge color="red">expired</x-status-badge>
                                    @elseif($user->status === 'unused')
                                        <x-status-badge color="gray">unused</x-status-badge>
                                    @else
                                        <x-status-badge color="orange">offline</x-status-badge>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $user->expires_at?->format('H:i M d, Y') ?? '—' }}</td>
                                <td class="text-muted">{{ $user->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-user fs-1"></i></span>
                                    <p class="text-uppercase text-muted small mb-0">No {{ $tab === 'hotspot' ? 'hotspot' : 'PPPoE' }} customers found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-filter-modal name="customers" :clear-url="route('customers.index', ['type' => $tab])">
            <div class="col-12 col-sm-6">
                <label class="form-label">Site</label>
                <select name="router_id" class="form-select">
                    <option value="">All</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" @selected(request('router_id') == $router->id)>{{ $router->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(($tab === 'hotspot' ? ['active', 'expired', 'offline', 'unused'] : ['active', 'expired', 'offline']) as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Package</label>
                <select name="plan_id" class="form-select">
                    <option value="">All</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $users->links('vendor.pagination.rp-circles') }}</div>

    {{-- === ADD CUSTOMER OFFCANVAS === --}}
    @php $oneispConnType = old('_connection_type', $tab); @endphp
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-customer" @if($errors->any() || request('add')) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Add Customer</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <form method="POST" id="rp-add-customer-form" action="{{ $oneispConnType === 'hotspot' ? route('hotspot-users.store') : route('pppoe-users.store') }}" class="d-flex flex-column h-100">
            @csrf
            <input type="hidden" name="_connection_type" id="rp-conn-type-input" value="{{ $oneispConnType }}">
            <input type="hidden" name="redirect_to" id="rp-conn-redirect-input" value="{{ route('customers.index', ['type' => $oneispConnType]) }}">

            <div class="offcanvas-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="John" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" class="form-control">
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone_number" required value="{{ old('phone_number') }}" placeholder="0712345678" class="form-control">
                        @error('phone_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="Street, town" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Connection Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="rp_conn_choice" id="rp-conn-hotspot" value="hotspot" class="form-selectgroup-input" data-rp-conn-toggle @checked($oneispConnType === 'hotspot')>
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-broadcast"></i> Hotspot</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="rp_conn_choice" id="rp-conn-pppoe" value="pppoe" class="form-selectgroup-input" data-rp-conn-toggle @checked($oneispConnType === 'pppoe')>
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-device-desktop"></i> PPPoE</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6" data-rp-conn-fields="pppoe">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" value="{{ old('username') }}" placeholder="e.g., johndoe" class="form-control">
                        @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6" data-rp-conn-fields="pppoe">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" value="{{ old('password') }}" placeholder="RADIUS login password" class="form-control">
                        @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Package</label>
                        <select name="current_plan_id" data-rp-conn-fields="hotspot" class="form-select">
                            <option value="">— None —</option>
                            @foreach($hotspotPlans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('current_plan_id') == $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                        <select name="current_plan_id" data-rp-conn-fields="pppoe" class="form-select">
                            <option value="">— None —</option>
                            @foreach($pppoePlans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('current_plan_id') == $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-device-floppy icon"></i> Save Customer
                </button>
            </div>
        </form>
    </div>


    <x-slot name="scripts">
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                    bootstrap.Offcanvas.getOrCreateInstance(el).show();
                });

                // Add-customer offcanvas: connection-type radios swap the form action, the
                // hidden _connection_type/redirect_to values, and which fields are shown.
                var form = document.getElementById('rp-add-customer-form');
                var typeInput = document.getElementById('rp-conn-type-input');
                var redirectInput = document.getElementById('rp-conn-redirect-input');
                var hotspotStoreUrl = '{{ route('hotspot-users.store') }}';
                var pppoeStoreUrl = '{{ route('pppoe-users.store') }}';
                var hotspotIndexUrl = '{{ route('customers.index', ['type' => 'hotspot']) }}';
                var pppoeIndexUrl = '{{ route('customers.index', ['type' => 'pppoe']) }}';

                function syncConnType() {
                    var type = document.getElementById('rp-conn-hotspot').checked ? 'hotspot' : 'pppoe';
                    form.action = type === 'hotspot' ? hotspotStoreUrl : pppoeStoreUrl;
                    typeInput.value = type;
                    redirectInput.value = type === 'hotspot' ? hotspotIndexUrl : pppoeIndexUrl;

                    document.querySelectorAll('[data-rp-conn-fields]').forEach(function (el) {
                        var show = el.getAttribute('data-rp-conn-fields') === type;
                        el.style.display = show ? '' : 'none';
                        if (el.tagName === 'SELECT') el.disabled = !show;
                        if (el.tagName !== 'SELECT') {
                            var input = el.querySelector('input');
                            if (input) input.required = show && (input.name === 'username' || input.name === 'password');
                        }
                    });
                }

                document.querySelectorAll('[data-rp-conn-toggle]').forEach(function (radio) {
                    radio.addEventListener('change', syncConnType);
                });
                syncConnType();

                // Live online/offline polling — matches hotspot-users/pppoe-users index pages.
                // Also drives the Online/Offline pill filter above the table: each row's
                // resolved live status is stashed on its <tr data-live-row> as data-live-status
                // ('online'|'offline'|'unknown' — unreachable router/no session data yet), and
                // the filter buttons just show/hide rows by that attribute — no server round-trip.
                var tab = '{{ $tab }}';
                var keys = @json($tab === 'hotspot' ? $users->pluck('phone_number') : $users->pluck('username'));

                var liveFilter = null; // null | 'online' | 'offline'
                var onlineBtn = document.getElementById('rp-live-filter-online');
                var offlineBtn = document.getElementById('rp-live-filter-offline');
                var onlineCountEl = document.getElementById('rp-live-count-online');
                var offlineCountEl = document.getElementById('rp-live-count-offline');

                function applyLiveFilter() {
                    var onlineCount = 0, offlineCount = 0;
                    document.querySelectorAll('tr[data-live-row]').forEach(function (row) {
                        var status = row.getAttribute('data-live-status');
                        if (status === 'online') onlineCount++;
                        if (status === 'offline') offlineCount++;
                        row.classList.toggle('d-none', !!liveFilter && status !== liveFilter);
                    });
                    onlineCountEl.textContent = onlineCount;
                    offlineCountEl.textContent = offlineCount;
                    onlineBtn.classList.toggle('active', liveFilter === 'online');
                    offlineBtn.classList.toggle('active', liveFilter === 'offline');
                }

                onlineBtn.addEventListener('click', function () {
                    liveFilter = liveFilter === 'online' ? null : 'online';
                    applyLiveFilter();
                });
                offlineBtn.addEventListener('click', function () {
                    liveFilter = liveFilter === 'offline' ? null : 'offline';
                    applyLiveFilter();
                });

                if (keys.length === 0) {
                    applyLiveFilter();
                    return;
                }

                async function poll() {
                    try {
                        var params = new URLSearchParams();
                        var paramName = tab === 'hotspot' ? 'phone_numbers[]' : 'usernames[]';
                        keys.forEach(function (k) { params.append(paramName, k); });
                        var url = tab === 'hotspot'
                            ? "{{ route('hotspot-users.live-status') }}?" + params
                            : "{{ route('pppoe-users.live-status') }}?" + params;
                        var res = await fetch(url, { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        var live = json.data || {};

                        document.querySelectorAll('[data-live-cell]').forEach(function (cell) {
                            var key = cell.getAttribute('data-live-cell');
                            var session = live[key];
                            var row = cell.closest('tr');
                            if (session && session.online) {
                                cell.innerHTML = '<span class="text-success fw-semibold">Online</span>';
                                row.setAttribute('data-live-status', 'online');
                            } else if (session) {
                                cell.innerHTML = '<span class="text-warning fw-semibold">Offline</span>';
                                row.setAttribute('data-live-status', 'offline');
                            } else {
                                cell.innerHTML = '<span class="text-muted">·</span>';
                                row.setAttribute('data-live-status', 'unknown');
                            }
                        });

                        applyLiveFilter();
                    } catch (e) {
                        // Leave badges as-is on a transient failure.
                    }
                }

                poll();
                setInterval(poll, 5000);
            });
        </script>
    </x-slot>
</x-sidebar-layout>

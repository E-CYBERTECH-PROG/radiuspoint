{{-- Expects $users (paginated PppoeUser|HotspotUser), $plans (current tab's Plan list, for
     the filter bar), $allPlans (all plans keyed by id, for row display), $pppoePlans/
     $hotspotPlans (for the Add Customer offcanvas' Package dropdown), $routers (keyed by id),
     $tab ('pppoe'|'hotspot'), $stats, $pppoeCount, $hotspotCount in scope. --}}
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
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-3">
        @foreach($oneispCustomerStatTiles as $tile)
            <div class="col">
                <div class="card card-sm">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <p class="fs-2 font-monospace fw-bold mb-0">{{ number_format($tile['value']) }}</p>
                            <p class="text-muted small mt-1 mb-0">{{ $tile['label'] }}</p>
                        </div>
                        <span class="avatar {{ $tile['bg'] }} flex-shrink-0"><i class="ti {{ $tile['icon'] }} fs-3"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- === FILTERS === --}}
    <form method="GET" class="card mb-3">
        <div class="card-body">
            <h3 class="card-title">Filters</h3>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
                <div class="col">
                    <label class="form-label">Site</label>
                    <select name="router_id" onchange="this.form.submit()" class="form-select">
                        <option value="">All</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" @selected(request('router_id') == $router->id)>{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Status</label>
                    <select name="status" onchange="this.form.submit()" class="form-select">
                        <option value="">All</option>
                        @foreach(($tab === 'hotspot' ? ['active', 'expired', 'offline', 'unused'] : ['active', 'expired', 'offline']) as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Connection</label>
                    <select name="tab" onchange="this.form.submit()" class="form-select">
                        <option value="pppoe" @selected($tab === 'pppoe')>PPPoE</option>
                        <option value="hotspot" @selected($tab === 'hotspot')>Hotspot</option>
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Package</label>
                    <select name="plan_id" onchange="this.form.submit()" class="form-select">
                        <option value="">All</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by username, name, or phone…" class="form-control">
                    @if(request('search'))
                        <a href="{{ route('customers.index', array_filter(array_merge(request()->except(['search', 'page']), ['tab' => $tab]))) }}" class="input-group-text" title="Clear search">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                </div>
            </div>
            @if(request()->hasAny(['search', 'status', 'plan_id', 'router_id']))
                <div class="mt-2">
                    <a href="{{ route('customers.index', ['tab' => $tab]) }}" class="small fw-bold">Clear filters</a>
                </div>
            @endif
        </div>
    </form>

    {{-- === TABS + ACTIONS === --}}
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a href="{{ route('customers.index', array_filter(['tab' => 'pppoe', 'status' => request('status'), 'search' => request('search')])) }}" class="nav-link {{ $tab === 'pppoe' ? 'active' : '' }}">
                    PPPoE ({{ $pppoeCount }})
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('customers.index', array_filter(['tab' => 'hotspot', 'status' => request('status'), 'search' => request('search')])) }}" class="nav-link {{ $tab === 'hotspot' ? 'active' : '' }}">
                    Hotspot ({{ $hotspotCount }})
                </a>
            </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
            <x-per-page-select />
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-customer">
                <i class="ti ti-user-plus icon"></i> New Customer
            </button>
        </div>
    </div>

    {{-- === ADD CUSTOMER OFFCANVAS === --}}
    @php $oneispConnType = old('_connection_type', $tab); @endphp
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-customer" style="--tblr-offcanvas-width:42rem" @if($errors->any() || request('add')) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Add Customer</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <form method="POST" id="rp-add-customer-form" action="{{ $oneispConnType === 'hotspot' ? route('hotspot-users.store') : route('pppoe-users.store') }}" class="d-flex flex-column h-100">
            @csrf
            <input type="hidden" name="_connection_type" id="rp-conn-type-input" value="{{ $oneispConnType }}">
            <input type="hidden" name="redirect_to" id="rp-conn-redirect-input" value="{{ route('customers.index') }}?tab={{ $oneispConnType }}">

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

    {{-- === TABLE === --}}
    <div class="card">
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
                            $oneispDetailUrl = route('customers.show', ['token' => \App\Http\Controllers\CustomerController::tokenFor($tab, $user->id)]);
                        @endphp
                        <tr>
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
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-user icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No {{ $tab === 'hotspot' ? 'hotspot' : 'PPPoE' }} customers found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>

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
                var customersIndexUrl = '{{ route('customers.index') }}';

                function syncConnType() {
                    var type = document.getElementById('rp-conn-hotspot').checked ? 'hotspot' : 'pppoe';
                    form.action = type === 'hotspot' ? hotspotStoreUrl : pppoeStoreUrl;
                    typeInput.value = type;
                    redirectInput.value = customersIndexUrl + '?tab=' + type;

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
                var tab = '{{ $tab }}';
                var keys = @json($tab === 'hotspot' ? $users->pluck('phone_number') : $users->pluck('username'));
                if (keys.length === 0) return;

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
                            if (session && session.online) {
                                cell.innerHTML = '<span class="text-success fw-semibold">Online</span>';
                            } else if (session) {
                                cell.innerHTML = '<span class="text-warning fw-semibold">Offline</span>';
                            } else {
                                cell.innerHTML = '<span class="text-muted">·</span>';
                            }
                        });
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

<x-sidebar-layout title="PPPoE Users">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">PPPoE Users</h1>
            <p class="text-muted mb-0">Fixed / fiber customers authenticating by username.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form action="{{ route('pppoe-users.purge-expired') }}" method="POST" onsubmit="return rpConfirm(event, 'Delete every expired PPPoE customer? This also removes their RADIUS credentials and cannot be undone.')">
                @csrf
                <button type="submit" class="btn">
                    <i class="ti ti-trash icon"></i> Purge Expired
                </button>
            </form>
            <a href="{{ route('pppoe-users.create') }}" class="btn btn-primary">
                <i class="ti ti-user-plus icon"></i> Add Customer
            </a>
        </div>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username or phone..." class="form-control">
        </div>
        <select name="status" class="form-select w-auto">
            <option value="">All Statuses</option>
            @foreach(['active', 'expired', 'offline'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="plan_id" class="form-select w-auto">
            <option value="">All Packages</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
            @endforeach
        </select>
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'status', 'plan_id']))
            <a href="{{ route('pppoe-users.index') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Package</th>
                        <th>Router</th>
                        <th>Expiry</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Live</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="font-monospace">
                                <button type="button" class="btn btn-link p-0" onclick="window.rpOpenUserPanel('{{ route('pppoe-users.panel', $user) }}')">
                                    {{ $user->username }}
                                </button>
                            </td>
                            <td class="text-muted">{{ $user->name ?: '—' }}</td>
                            <td class="text-muted">{{ $user->phone_number ?: '—' }}</td>
                            <td class="text-muted">{{ $plans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="text-muted">{{ $routers[$user->current_router_id]->name ?? '—' }}</td>
                            <td class="text-muted">{{ $user->expires_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-center">
                                @if($user->status === 'active')
                                    <x-status-badge color="green" dot>Active</x-status-badge>
                                @elseif($user->status === 'expired')
                                    <x-status-badge color="amber">Expired</x-status-badge>
                                @else
                                    <x-status-badge color="red">Offline</x-status-badge>
                                @endif
                                @if($user->fup_throttled_at)
                                    <span class="d-block mt-1 text-orange text-uppercase" style="font-size:.625rem;letter-spacing:.05em" title="Throttled since {{ $user->fup_throttled_at->diffForHumans() }}">
                                        <i class="ti ti-gauge"></i> FUP
                                    </span>
                                @endif
                            </td>
                            <td class="text-center" data-live-cell data-username="{{ $user->username }}">
                                <span class="text-muted">·</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    @if($user->current_router_id)
                                        <button type="button" class="text-muted" style="background:none;border:0;display:none" data-live-disconnect data-username="{{ $user->username }}" data-router="{{ $user->current_router_id }}" title="Disconnect"><i class="ti ti-plug-connected-x"></i></button>
                                    @endif
                                    <a href="{{ route('pppoe-users.edit', $user) }}" class="text-muted" title="Edit"><i class="ti ti-edit"></i></a>
                                    <form action="{{ route('pppoe-users.destroy', $user) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this customer?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="ti ti-user icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No PPPoE customers yet.</p>
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
            (function () {
                var usernames = @json($users->pluck('username'));
                if (usernames.length === 0) return;

                var disconnectUrlTemplate = "{{ route('routers.actions.disconnect-pppoe', ['router' => '__ROUTER__']) }}";
                var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                var live = {};

                function render() {
                    document.querySelectorAll('[data-live-cell]').forEach(function (cell) {
                        var username = cell.getAttribute('data-username');
                        var session = live[username];
                        if (session && session.online) {
                            cell.innerHTML = '<span class="badge bg-green-lt"><span class="badge bg-green me-1" style="width:.5rem;height:.5rem;padding:0"></span>Online</span>';
                        } else if (session) {
                            cell.innerHTML = '<span class="text-muted text-uppercase" style="font-size:.625rem">Offline</span>';
                        } else {
                            cell.innerHTML = '<span class="text-muted">·</span>';
                        }
                    });

                    document.querySelectorAll('[data-live-disconnect]').forEach(function (btn) {
                        var username = btn.getAttribute('data-username');
                        var session = live[username];
                        btn.style.display = session && session.online ? '' : 'none';
                    });
                }

                async function poll() {
                    try {
                        var params = new URLSearchParams();
                        usernames.forEach(function (u) { params.append('usernames[]', u); });
                        var res = await fetch("{{ route('pppoe-users.live-status') }}?" + params, { headers: { Accept: 'application/json' } });
                        var json = await res.json();
                        live = json.data || {};
                        render();
                    } catch (e) {
                        // Leave `live` as-is on a transient failure — badges just stop updating
                        // until the next successful poll rather than flashing to "unknown".
                    }
                }

                async function disconnect(username, routerId) {
                    var session = live[username];
                    if (!session || !session.id) return;
                    if (!(await window.rpConfirmAsync('Disconnect ' + username + '?'))) return;
                    try {
                        await fetch(disconnectUrlTemplate.replace('__ROUTER__', routerId), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                            body: JSON.stringify({ id: session.id }),
                        });
                        poll();
                    } catch (e) {
                        // Next poll cycle will reflect true state either way.
                    }
                }

                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-live-disconnect]');
                    if (btn) disconnect(btn.getAttribute('data-username'), btn.getAttribute('data-router'));
                });

                poll();
                setInterval(poll, 5000);
            })();
        </script>
    </x-slot>
</x-sidebar-layout>

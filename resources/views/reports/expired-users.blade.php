<x-sidebar-layout title="Expired Users">
    <div class="mb-4">
        <h1 class="mb-1">Expired Users</h1>
        <p class="text-muted mb-0">Hotspot and PPPoE accounts past their expiry.</p>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or username..." class="form-control">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control w-auto">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control w-auto">
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'from', 'to']))
            <a href="{{ route('reports.expired-users') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Identifier</th>
                        <th>Package</th>
                        <th>Router</th>
                        <th class="text-end">Expired</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <x-status-badge color="{{ $user->type === 'hotspot' ? 'orange' : 'blue' }}">{{ ucfirst($user->type) }}</x-status-badge>
                            </td>
                            <td>
                                <div class="fw-bold font-monospace">{{ $user->identifier }}</div>
                                @if($user->name)
                                    <div class="text-muted small">{{ $user->name }}</div>
                                @endif
                            </td>
                            <td class="text-muted">{{ $plans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="text-muted">{{ $routers[$user->current_router_id]->name ?? '—' }}</td>
                            <td class="text-end text-muted small">{{ $user->expires_at ? \Carbon\Carbon::parse($user->expires_at)->diffForHumans() : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-circle-check icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No expired users right now.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
</x-sidebar-layout>

<x-sidebar-layout title="Expired Users">
    <div class="mb-4">
        <h1 class="mb-1">Expired Users</h1>
        <p class="text-muted mb-0">Hotspot and PPPoE accounts past their expiry.</p>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-expired-users" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or username…" class="form-control">
                </div>
            </div>

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
                            <td colspan="5" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-circle-check fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No expired users right now.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <x-filter-modal name="expired-users" :clear-url="route('reports.expired-users')">
            <div class="col-12 col-sm-6">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $users->links() }}</div>
</x-sidebar-layout>

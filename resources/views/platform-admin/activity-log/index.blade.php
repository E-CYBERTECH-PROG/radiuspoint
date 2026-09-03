<x-sidebar-layout title="Activity Log">
    <div class="mb-4">
        <h1 class="mb-1">Admin Activity Log</h1>
        <p class="text-muted mb-0">Audit trail of platform admin actions on tenant accounts.</p>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select :default="30" />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-activity-log" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Admin</th>
                        <th>Tenant</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="fw-bold">{{ $log->admin?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $log->tenant?->company_name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-primary-lt">{{ str_replace('_', ' ', $log->action) }}</span>
                            </td>
                            <td class="text-muted">{{ $log->details ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-history fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No activity recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
        </div>

        <x-filter-modal name="activity-log" :clear-url="route('platform-admin.activity-log.index')">
            <div class="col-12">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select">
                    <option value="">All</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Admin</label>
                <select name="admin_user_id" class="form-select">
                    <option value="">All</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" @selected(request('admin_user_id') == $admin->id)>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-modal>
    </form>
</x-sidebar-layout>

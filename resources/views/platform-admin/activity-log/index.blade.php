<x-sidebar-layout title="Activity Log">
    <div class="mb-4">
        <h1 class="mb-1">Admin Activity Log</h1>
        <p class="text-muted mb-0">Audit trail of platform admin actions on tenant accounts.</p>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <select name="tenant_id" class="form-select flex-fill">
            <option value="">All Tenants</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->company_name }}</option>
            @endforeach
        </select>
        <select name="admin_user_id" class="form-select flex-fill">
            <option value="">All Admins</option>
            @foreach($admins as $admin)
                <option value="{{ $admin->id }}" @selected(request('admin_user_id') == $admin->id)>{{ $admin->name }}</option>
            @endforeach
        </select>
        <select name="action" class="form-select flex-fill">
            <option value="">All Actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['tenant_id', 'admin_user_id', 'action']))
            <a href="{{ route('platform-admin.activity-log.index') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
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
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-history icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No activity recorded yet.</p>
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
</x-sidebar-layout>

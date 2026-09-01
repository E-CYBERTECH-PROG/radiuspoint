<x-sidebar-layout title="Tenants">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Tenant Accounts</h1>
            <p class="text-muted mb-0">Manage ISP companies using RadiusPoint.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('platform-admin.tenants.export', request()->query()) }}" class="btn">
                <i class="ti ti-download icon"></i> Export CSV
            </a>
            <a href="{{ route('platform-admin.tenants.import-form') }}" class="btn btn-primary">
                <i class="ti ti-upload icon"></i> Import Tenants
            </a>
        </div>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company name..." class="form-control">
        </div>
        <select name="status" class="form-select w-auto">
            <option value="">All Statuses</option>
            @foreach(['pending', 'active', 'suspended', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="tier" class="form-select w-auto">
            <option value="">All Tiers</option>
            @foreach(['free', 'starter', 'pro'] as $tier)
                <option value="{{ $tier }}" @selected(request('tier') === $tier)>{{ ucfirst($tier) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'status', 'tier']))
            <a href="{{ route('platform-admin.tenants.index') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Owner</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Subscription</th>
                        <th>Signed Up</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr>
                            <td class="fw-bold">{{ $tenant->company_name }}</td>
                            <td class="text-muted">
                                @if($owner = $tenant->users->first())
                                    {{ $owner->name }}<br><span class="small text-muted">{{ $owner->email }}</span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $statusColors = ['pending' => 'amber', 'active' => 'green', 'suspended' => 'secondary', 'rejected' => 'red'];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$tenant->status] ?? 'secondary' }}-lt">{{ $tenant->status }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-lt">{{ $tenant->subscription_tier }}</span>
                            </td>
                            <td class="text-muted">{{ $tenant->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="text-muted" title="View"><i class="ti ti-eye"></i></a>
                                    @if($tenant->status === 'pending')
                                        <form action="{{ route('platform-admin.tenants.approve', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm text-success p-0 text-uppercase">Approve</button>
                                        </form>
                                        <form action="{{ route('platform-admin.tenants.reject', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-uppercase">Reject</button>
                                        </form>
                                    @elseif($tenant->status === 'active')
                                        <form action="{{ route('platform-admin.tenants.suspend', $tenant) }}" method="POST" onsubmit="return rpConfirm(event, 'Suspend this tenant?')">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-uppercase">Suspend</button>
                                        </form>
                                    @elseif($tenant->status === 'suspended')
                                        <form action="{{ route('platform-admin.tenants.reactivate', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm text-success p-0 text-uppercase">Reactivate</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-building icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No tenants match these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
            <div class="card-footer">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>
</x-sidebar-layout>

<x-sidebar-layout title="Tenants">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Tenant Accounts</h1>
            <p class="text-muted mb-0">Manage ISP companies using RadiusPoint.</p>
        </div>
        <a href="{{ route('platform-admin.tenants.import-form') }}" class="btn btn-primary">
            <i class="ti ti-upload icon"></i> <span class="d-none d-sm-inline">Import Tenants</span>
        </a>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-tenants" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company name…" class="form-control">
                    </div>
                    <a href="{{ route('platform-admin.tenants.export', request()->query()) }}" class="btn">
                        <i class="ti ti-download icon"></i> <span class="d-none d-sm-inline">Export CSV</span>
                    </a>
                </div>
            </div>

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
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-building fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No tenants match these filters.</p>
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

        <x-filter-modal name="tenants" :clear-url="route('platform-admin.tenants.index')">
            <div class="col-12">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['pending', 'active', 'suspended', 'rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Tier</label>
                <select name="tier" class="form-select">
                    <option value="">All</option>
                    @foreach(['free', 'starter', 'pro'] as $tier)
                        <option value="{{ $tier }}" @selected(request('tier') === $tier)>{{ ucfirst($tier) }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-modal>
    </form>
</x-sidebar-layout>

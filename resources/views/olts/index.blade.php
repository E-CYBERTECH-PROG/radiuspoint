<x-sidebar-layout title="OLT Devices">
    <div class="mb-4">
        <h1 class="mb-1">OLT Devices</h1>
        <p class="text-muted mb-0">Remote access to your GPON/EPON OLTs (VSOL, Hioso, and similar).</p>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name…" class="form-control">
                    </div>
                    <a href="{{ route('olts.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">Add OLT</span>
                    </a>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>IP Address</th>
                        <th>PON Ports</th>
                        <th class="text-center">Status</th>
                        <th>Last Seen</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($olts as $olt)
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('olts.show', $olt) }}">{{ $olt->name }}</a>
                            </td>
                            <td class="text-muted text-uppercase small fw-bold">{{ $olt->brand }}</td>
                            <td class="text-muted font-monospace">{{ $olt->ip_address }}:{{ $olt->ssh_port }}</td>
                            <td class="text-muted">{{ $olt->pon_ports ?: '—' }}</td>
                            <td class="text-center">
                                @if($olt->status === 'active')
                                    <x-status-badge color="green" dot>Active</x-status-badge>
                                @elseif($olt->status === 'offline')
                                    <x-status-badge color="red">Offline</x-status-badge>
                                @else
                                    <x-status-badge color="gray">Pending</x-status-badge>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $olt->last_seen?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('olts.show', $olt) }}" class="text-muted" title="Open"><i class="ti ti-arrow-right"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-affiliate fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No OLTs added yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </form>

    <div class="mt-3">{{ $olts->links('vendor.pagination.rp-circles') }}</div>
</x-sidebar-layout>

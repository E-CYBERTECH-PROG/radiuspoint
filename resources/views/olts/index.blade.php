<x-sidebar-layout title="OLT Devices">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">OLT Devices</h1>
            <p class="text-muted mb-0">Remote access to your GPON/EPON OLTs (VSOL, Hioso, and similar).</p>
        </div>
        <a href="{{ route('olts.create') }}" class="btn btn-primary">
            <i class="ti ti-plus icon"></i> Add OLT
        </a>
    </div>

    <div class="card">
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
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-affiliate icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No OLTs added yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $olts->links() }}</div>
</x-sidebar-layout>

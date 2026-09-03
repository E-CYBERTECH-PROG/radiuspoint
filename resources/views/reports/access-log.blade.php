<x-sidebar-layout title="Access Requests">
    <div class="mb-4">
        <h1 class="mb-1">Access Requests</h1>
        <p class="text-muted mb-0">RADIUS authentication sessions across your routers.</p>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select :default="50" />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-access-log" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username…" class="form-control">
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Router</th>
                        <th>Framed IP</th>
                        <th>Session Start</th>
                        <th>Duration</th>
                        <th>Data Usage</th>
                        <th class="text-end">Terminate Cause</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="fw-bold font-monospace">{{ $log->username }}</td>
                            <td class="text-muted">{{ $routersByIp[$log->nasipaddress]->name ?? $log->nasipaddress }}</td>
                            <td class="text-muted font-monospace">{{ $log->framedipaddress ?: '—' }}</td>
                            <td class="text-muted">{{ $log->acctstarttime ? \Carbon\Carbon::parse($log->acctstarttime)->format('d M Y H:i') : '—' }}</td>
                            <td class="text-muted font-monospace">
                                @if($log->acctsessiontime)
                                    {{ sprintf('%02d:%02d:%02d', floor($log->acctsessiontime / 3600), floor(($log->acctsessiontime % 3600) / 60), $log->acctsessiontime % 60) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted font-monospace">
                                {{ number_format((($log->acctinputoctets ?? 0) + ($log->acctoutputoctets ?? 0)) / 1048576, 1) }} MB
                            </td>
                            <td class="text-end text-muted small">{{ $log->acctterminatecause ?: 'Active' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-shield fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No access requests recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links('vendor.pagination.rp-circles') }}
            </div>
        @endif
        </div>

        <x-filter-modal name="access-log" :clear-url="route('reports.access-log')">
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
</x-sidebar-layout>

<x-sidebar-layout title="Access Requests">
    <div class="mb-4">
        <h1 class="mb-1">Access Requests</h1>
        <p class="text-muted mb-0">RADIUS authentication sessions across your routers.</p>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username..." class="form-control">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control w-auto">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control w-auto">
        <x-per-page-select :default="50" />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'from', 'to']))
            <a href="{{ route('reports.access-log') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
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
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-shield icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No access requests recorded yet.</p>
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

<x-sidebar-layout title="PPPoE Account Status">
    <div class="mb-4">
        <h1 class="mb-1">PPPoE Account Status &amp; Expiry</h1>
        <p class="text-muted mb-0">No prepaid wallet balance is tracked — this is account status, not a monetary figure.</p>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username..." class="form-control">
        </div>
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search']))
            <a href="{{ route('reports.pppoe-balances') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Expiry</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="fw-bold font-monospace">{{ $user->username }}</td>
                            <td class="text-muted">{{ $user->name ?: '—' }}</td>
                            <td class="text-muted">{{ $plans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="font-monospace">{{ isset($plans[$user->current_plan_id]) ? 'KES ' . number_format($plans[$user->current_plan_id]->price) : '—' }}</td>
                            <td class="text-muted">{{ $user->expires_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                @if($user->status === 'active')
                                    <span class="badge bg-green-lt">Active</span>
                                @elseif($user->status === 'expired')
                                    <span class="badge bg-yellow-lt">Expired</span>
                                @else
                                    <span class="badge bg-red-lt">Offline</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-user icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No PPPoE accounts yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
</x-sidebar-layout>

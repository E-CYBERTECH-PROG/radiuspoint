<x-sidebar-layout title="PPPoE Account Status">
    <div class="mb-4">
        <h1 class="mb-1">PPPoE Account Status &amp; Expiry</h1>
        <p class="text-muted mb-0">No prepaid wallet balance is tracked — this is account status, not a monetary figure.</p>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
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
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-user fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No PPPoE accounts yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </form>

    <div class="mt-3">{{ $users->links() }}</div>
</x-sidebar-layout>

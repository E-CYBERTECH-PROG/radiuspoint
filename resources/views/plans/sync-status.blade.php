<x-sidebar-layout title="Sync Status — {{ $plan->name }}">
    <div class="mb-4">
        <a href="{{ route('plans.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to Plans
        </a>
        <h1 class="mb-1">{{ $plan->name }}</h1>
        <p class="text-muted mb-0">
            Per-router sync status — updated automatically every minute by the background reconciler, no manual re-sync needed.
        </p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Router</th>
                        <th class="text-center">Status</th>
                        <th>Message</th>
                        <th class="text-end">Last Synced</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routers as $router)
                        @php $sync = $syncs->get($router->id); @endphp
                        <tr>
                            <td class="fw-bold">{{ $router->name }}</td>
                            <td class="text-center">
                                @if(! $sync)
                                    <x-status-badge color="gray" icon="ti-clock">Awaiting first sync</x-status-badge>
                                @elseif($sync->status === 'synced')
                                    <x-status-badge color="green" dot>Synced</x-status-badge>
                                @else
                                    <x-status-badge color="red" dot>Failed</x-status-badge>
                                @endif
                            </td>
                            <td class="text-muted">{{ $sync->message ?? '—' }}</td>
                            <td class="text-end text-muted small">{{ $sync?->synced_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-router icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No active routers to sync to.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-sidebar-layout>

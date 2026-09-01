<x-sidebar-layout title="Notifications">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Notifications</h1>
            <p class="text-muted mb-0">Full history of alerts sent to your account.</p>
        </div>
        <a href="{{ route('settings.notifications.edit') }}" class="btn">
            <i class="ti ti-settings icon"></i> Preferences
        </a>
    </div>

    <div class="list-group">
        @forelse($notifications as $notification)
            @php $url = $notification->data['url'] ?? null; @endphp
            <a href="{{ $url ?? '#' }}" class="list-group-item list-group-item-action d-flex align-items-start gap-3 {{ $notification->read_at ? '' : 'bg-blue-lt' }}">
                @unless($notification->read_at)
                    <span class="rounded-circle bg-blue mt-1 flex-shrink-0" style="width:.5rem;height:.5rem"></span>
                @else
                    <span class="flex-shrink-0" style="width:.5rem"></span>
                @endunless
                <div class="flex-fill">
                    <p class="fw-bold mb-0">{{ $notification->data['message'] ?? 'Notification' }}</p>
                    <p class="text-muted small mt-1 mb-0">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            </a>
        @empty
            <div class="text-center text-muted py-5">
                <i class="ti ti-bell-off icon icon-lg mb-2 d-block"></i>
                <p class="text-uppercase small mb-0">No notifications yet.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
</x-sidebar-layout>

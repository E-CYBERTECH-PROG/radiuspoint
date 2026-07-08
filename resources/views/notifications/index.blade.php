<x-sidebar-layout title="Notifications">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Full history of alerts sent to your account.</p>
        </div>
        <a href="{{ route('settings.notifications.edit') }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
            <i class="bx bx-cog text-lg"></i> Preferences
        </a>
    </div>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        @forelse($notifications as $notification)
            @php $url = $notification->data['url'] ?? null; @endphp
            <a href="{{ $url ?? '#' }}" class="block px-6 py-4 border-b border-gray-50 dark:border-gray-900 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors flex items-start gap-3 {{ $notification->read_at ? '' : 'bg-blue-50/50 dark:bg-blue-900/10' }}">
                @unless($notification->read_at)
                    <span class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 shrink-0"></span>
                @else
                    <span class="w-2 h-2 shrink-0"></span>
                @endunless
                <div class="flex-1">
                    <p class="text-sm text-gray-900 dark:text-white font-bold">{{ $notification->data['message'] ?? 'Notification' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            </a>
        @empty
            <div class="px-6 py-12 text-center text-gray-400">
                <i class="bx bx-bell-off text-4xl mb-3 text-gray-200"></i>
                <p class="text-xs tracking-widest uppercase">No notifications yet.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</x-sidebar-layout>

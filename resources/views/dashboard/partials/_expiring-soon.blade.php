{{-- Expects $expiringUsers in scope. --}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-md font-bold text-gray-900 dark:text-white">Expiring Soon</h3>
        <span class="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 text-xs px-2 py-1 rounded-full font-bold">{{ $expiringUsers->count() }} Users</span>
    </div>
    <div class="space-y-4">
        @forelse($expiringUsers as $user)
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="bx bx-user text-lg text-gray-400"></i>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ $user->label }}</p>
                    <p class="text-xs text-red-500 font-bold">Expires {{ \Carbon\Carbon::parse($user->expires_at)->diffForHumans() }}</p>
                </div>
            </div>
            <form action="{{ route('sms.store') }}" method="POST">
                @csrf
                <input type="hidden" name="phone_number" value="{{ $user->label }}">
                <input type="hidden" name="message" value="Reminder: your internet package expires {{ \Carbon\Carbon::parse($user->expires_at)->diffForHumans() }}. Top up to stay connected.">
                <button type="submit" class="text-gray-400 hover:text-blue-500 transition-colors" title="Send SMS Reminder">
                    <i class="bx bx-envelope text-lg"></i>
                </button>
            </form>
        </div>
        @empty
        <p class="text-sm text-gray-500">No users expiring in the next 24 hours.</p>
        @endforelse
    </div>
</div>

{{-- Expects a live-updating `mpesaStatus` on the closest x-data="dashboard(...)" ancestor. --}}
<div class="grid grid-cols-2 gap-3 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <a href="{{ route('sms.index') }}" class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow">
        <i class="bx bx-message-square-dots text-xl text-blue-500"></i>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1.5 mb-0.5">SMS Gateway</p>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Log Mode</h3>
    </a>
    <a href="{{ route('mpesa-settings.edit') }}" class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow">
        <i class="bx bx-credit-card text-xl" :class="mpesaStatus.state === 'degraded' ? 'text-red-500' : (mpesaStatus.state === 'active' ? 'text-green-500' : 'text-gray-400')"></i>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1.5 mb-0.5">M-Pesa</p>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white" x-text="mpesaStatus.label"></h3>
    </a>
</div>

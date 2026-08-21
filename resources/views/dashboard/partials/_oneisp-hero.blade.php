{{-- Expects $stats/$currency in scope, plus an optional $delay (ms). --}}
@php
    $oneispHour = now()->hour;
    $oneispGreeting = $oneispHour < 12 ? 'Good morning' : ($oneispHour < 17 ? 'Good afternoon' : 'Good evening');
    $oneispFirstName = explode(' ', Auth::user()->name)[0] ?? Auth::user()->name;
@endphp
<div class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-5 h-full overflow-hidden rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="absolute -right-6 -top-6 w-32 h-32 rounded-full bg-indigo-50 dark:bg-indigo-900/20"></div>

    <div class="relative flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $oneispGreeting }}, {{ $oneispFirstName }}!</p>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mt-3">Today's Earnings</p>
            <p class="text-2xl font-fira font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">{{ $currency }} {{ number_format($stats['income_today'] ?? 0, 1) }}</p>
        </div>

        <div class="relative shrink-0" aria-hidden="true">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-300 to-amber-500 flex items-center justify-center shadow-md ring-4 ring-white dark:ring-gray-950">
                <i class="bx bxs-medal text-2xl text-white"></i>
            </div>
            <div class="absolute left-1/2 -translate-x-1/2 top-9 w-0 h-0 border-l-[7px] border-l-transparent border-r-[7px] border-r-transparent border-t-[12px] border-t-indigo-600"></div>
        </div>
    </div>
</div>

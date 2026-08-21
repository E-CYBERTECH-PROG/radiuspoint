{{-- Expects $stats/$currency/$incomeMonthDelta in scope. --}}
@php
    $oneispDelta = $incomeMonthDelta ?? 0;
    $oneispIsUp = $oneispDelta >= 0;
    // SVG ring: circumference for r=15.5 is ~97.4; offset shrinks as |delta| grows toward 100%.
    $oneispRingPct = max(0, min(100, abs($oneispDelta)));
    $oneispRingCircumference = 97.4;
    $oneispRingOffset = $oneispRingCircumference - ($oneispRingPct / 100 * $oneispRingCircumference);
@endphp
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-4 h-full flex items-center justify-between gap-3 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="min-w-0">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Earnings</p>
        <p class="text-[11px] text-gray-400 mt-2">This Month</p>
        <p class="text-lg font-fira font-extrabold text-gray-900 dark:text-white truncate">{{ $currency }} {{ number_format($stats['income_month'] ?? 0) }}</p>
    </div>

    <div class="relative shrink-0 w-16 h-16">
        <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90">
            <circle cx="18" cy="18" r="15.5" fill="none" class="stroke-gray-100 dark:stroke-gray-800" stroke-width="3"></circle>
            <circle cx="18" cy="18" r="15.5" fill="none"
                    class="{{ $oneispIsUp ? 'stroke-emerald-500' : 'stroke-rose-500' }}"
                    stroke-width="3" stroke-linecap="round"
                    stroke-dasharray="{{ $oneispRingCircumference }}"
                    stroke-dashoffset="{{ $oneispRingOffset }}"></circle>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-xs font-fira font-bold {{ $oneispIsUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">{{ $oneispIsUp ? '+' : '-' }}{{ number_format(abs($oneispDelta), 1) }}%</span>
            <span class="text-[8px] text-gray-400 uppercase tracking-wide">Profit</span>
        </div>
    </div>
</div>

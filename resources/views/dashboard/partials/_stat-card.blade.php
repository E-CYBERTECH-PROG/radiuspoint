{{--
    Five compact stat cells with a date/time strip, shared by all 3 dashboard layouts.
    Expects the parent's $stats/$currency/$incomeTodayDelta/$incomeMonthDelta in scope,
    plus an optional $delay (ms).
--}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 rounded-xl shadow-sm overflow-hidden mb-3 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms" x-data="rpClock()" x-init="tick()">
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900/40">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400" x-text="dateText"></p>
        <p class="text-xs font-fira font-bold text-gray-400 tabular-nums" x-text="timeText"></p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-x-0 sm:divide-x divide-y lg:divide-y-0 divide-gray-100 dark:divide-gray-800">
        <div class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">Online Now</p>
                <button type="button" @click="toggleOnlineHidden()" class="text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 transition-colors shrink-0" title="Hide/show this number">
                    <i class="bx text-sm" :class="onlineHidden ? 'bx-hide' : 'bx-show'"></i>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <i class="bx bx-broadcast text-lg sm:text-xl text-green-500 shrink-0"></i>
                <div class="relative h-7 flex items-center min-w-0">
                    <span x-show="!onlineHidden" class="text-lg sm:text-xl font-fira font-bold text-gray-900 dark:text-white tabular-nums transition-transform duration-200" :class="pulse" x-text="onlineCount"></span>
                    <span x-show="onlineHidden" x-cloak class="text-lg sm:text-xl font-fira font-bold text-gray-300 dark:text-gray-700 tracking-widest select-none">&bull;&bull;&bull;</span>
                    <template x-for="delta in deltas" :key="delta.id">
                        <span class="absolute left-full ml-2 top-0 text-sm font-bold whitespace-nowrap"
                              :class="delta.value > 0 ? 'text-green-500 rp-delta-in' : 'text-red-500 rp-delta-out'"
                              x-text="(delta.value > 0 ? '+' : '') + delta.value"></span>
                    </template>
                </div>
            </div>
        </div>

        <div class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">Income Today</p>
                <button type="button" @click="toggleMoney()" class="text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 transition-colors shrink-0" title="Hide/show money values">
                    <i class="bx text-sm" :class="moneyHidden ? 'bx-hide' : 'bx-show'"></i>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <i class="bx bx-money text-lg sm:text-xl text-blue-500 shrink-0"></i>
                <h3 x-show="!moneyHidden" class="text-lg sm:text-xl font-fira font-bold text-gray-900 dark:text-white truncate">{{ $currency }} {{ number_format($stats['income_today'] ?? 0) }}</h3>
                <h3 x-show="moneyHidden" x-cloak class="text-lg sm:text-xl font-fira font-bold text-gray-300 dark:text-gray-700 tracking-widest select-none">••••••</h3>
            </div>
            @if($incomeTodayDelta !== null)
                <span x-show="!moneyHidden" class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $incomeTodayDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                    <i class="bx {{ $incomeTodayDelta >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i> {{ number_format(abs($incomeTodayDelta), 0) }}%
                </span>
            @endif
        </div>

        <div class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">This Month</p>
                <i class="bx bx-wallet text-base sm:text-lg text-blue-500 shrink-0"></i>
            </div>
            <h3 x-show="!moneyHidden" class="text-lg sm:text-xl font-fira font-bold text-gray-900 dark:text-white truncate">{{ $currency }} {{ number_format($stats['income_month'] ?? 0) }}</h3>
            <h3 x-show="moneyHidden" x-cloak class="text-lg sm:text-xl font-fira font-bold text-gray-300 dark:text-gray-700 tracking-widest select-none">••••••</h3>
            @if($incomeMonthDelta !== null)
                <span x-show="!moneyHidden" class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $incomeMonthDelta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                    <i class="bx {{ $incomeMonthDelta >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i> {{ number_format(abs($incomeMonthDelta), 0) }}%
                </span>
            @endif
        </div>

        <div class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">Active Users</p>
                <i class="bx bx-wifi text-base sm:text-lg text-indigo-500 shrink-0"></i>
            </div>
            <div class="flex items-baseline gap-3">
                <div class="flex items-baseline gap-1">
                    <h3 class="text-lg sm:text-xl font-fira font-bold text-blue-600 dark:text-blue-400">{{ $stats['hotspot_active'] ?? 0 }}</h3>
                    <span class="text-xs text-gray-500">Hotspot</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <h3 class="text-lg sm:text-xl font-fira font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['pppoe_active'] ?? 0 }}</h3>
                    <span class="text-xs text-gray-500">PPPoE</span>
                </div>
            </div>
        </div>

        <div class="p-3 sm:p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">Network</p>
                <i class="bx {{ ($stats['routers_offline'] ?? 0) > 0 ? 'bx-error text-red-500' : 'bx-check-shield text-green-500' }} text-base sm:text-lg shrink-0"></i>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-lg sm:text-xl font-fira font-bold {{ ($stats['routers_offline'] ?? 0) > 0 ? 'text-red-500' : 'text-green-600 dark:text-green-400' }}">{{ $stats['routers_offline'] ?? 0 }}</h3>
                <span class="text-xs text-gray-500">Offline</span>
            </div>
        </div>
    </div>
</div>

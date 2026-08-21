{{-- Expects $stats in scope, plus an optional $delay (ms). --}}
@php
    $oneispCustomerTiles = [
        ['label' => 'Total', 'value' => $stats['customers_total'] ?? 0, 'icon' => 'bx-user', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'text' => 'text-indigo-600 dark:text-indigo-400'],
        ['label' => 'Active', 'value' => ($stats['hotspot_active'] ?? 0) + ($stats['pppoe_active'] ?? 0), 'icon' => 'bx-user-check', 'bg' => 'bg-sky-50 dark:bg-sky-900/20', 'text' => 'text-sky-600 dark:text-sky-400'],
        ['label' => 'Expired', 'value' => $stats['customers_expired'] ?? 0, 'icon' => 'bx-user-x', 'bg' => 'bg-rose-50 dark:bg-rose-900/20', 'text' => 'text-rose-600 dark:text-rose-400'],
        ['label' => 'Online', 'value' => $stats['online_now'] ?? 0, 'icon' => 'bx-wifi', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-600 dark:text-emerald-400'],
        ['label' => 'Online PPP', 'value' => $stats['online_ppp'] ?? 0, 'icon' => 'bx-desktop', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-600 dark:text-emerald-400'],
        ['label' => 'Online Hotspot', 'value' => $stats['online_hotspot'] ?? 0, 'icon' => 'bx-broadcast', 'bg' => 'bg-teal-50 dark:bg-teal-900/20', 'text' => 'text-teal-600 dark:text-teal-400'],
    ];
@endphp
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-5 h-full rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Customers</h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($oneispCustomerTiles as $tile)
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full {{ $tile['bg'] }} flex items-center justify-center shrink-0">
                    <i class="bx {{ $tile['icon'] }} text-lg {{ $tile['text'] }}"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-base font-fira font-bold text-gray-900 dark:text-white leading-tight">{{ number_format($tile['value']) }}</p>
                    <p class="text-[11px] text-gray-400 truncate">{{ $tile['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>

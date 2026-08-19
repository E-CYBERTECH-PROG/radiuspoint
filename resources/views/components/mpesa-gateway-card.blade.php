@props(['setting', 'label', 'icon', 'color'])

@php
    $iconClasses = [
        'blue' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
        'amber' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
    ][$color] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';

    $gatewayLabels = ['till' => 'Till (Buy Goods)', 'paybill' => 'Paybill', 'bank' => 'Bank'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full text-left bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-5 flex items-center justify-between gap-4 cursor-pointer hover:border-gray-300 dark:hover:border-gray-700 transition-colors']) }}>
    <div class="flex items-center gap-4 min-w-0">
        <div class="p-3 rounded-lg shrink-0 {{ $iconClasses }}">
            <i class="bx {{ $icon }} text-xl"></i>
        </div>
        <div class="min-w-0">
            <p class="font-bold text-gray-900 dark:text-white truncate">
                {{ $label }} &middot; {{ $gatewayLabels[$setting->gateway_type] ?? ucfirst($setting->gateway_type) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-fira">
                {{ $setting->shortcode ?: 'No shortcode set' }} &middot; {{ ucfirst($setting->environment) }}
            </p>
        </div>
    </div>
    <div class="flex items-center gap-3 shrink-0">
        <x-status-badge :color="$setting->is_active ? 'green' : 'gray'" :dot="true" :pulse="$setting->is_active">
            {{ $setting->is_active ? 'Active' : 'Inactive' }}
        </x-status-badge>
        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">Edit</span>
    </div>
</div>

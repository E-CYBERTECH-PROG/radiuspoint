@props(['color' => 'gray', 'dot' => false, 'pulse' => false, 'icon' => null, 'plain' => false])

@php
    // Matches the one-isp reference: a plain light pill for state columns (Status, package
    // Active/Inactive) — no border, no uppercase tracking, no dot — and a bare bold colored
    // text treatment (plain) for the Online/Offline column, which isn't pilled there at all.
    $palette = [
        'green' => 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/20',
        'amber' => 'text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/20',
        'red' => 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/20',
        'blue' => 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20',
        'orange' => 'text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/20',
        'gray' => 'text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800/60',
    ];
    $plainText = [
        'green' => 'text-green-600 dark:text-green-400', 'amber' => 'text-amber-600 dark:text-amber-400',
        'red' => 'text-red-600 dark:text-red-400', 'blue' => 'text-blue-600 dark:text-blue-400',
        'orange' => 'text-orange-600 dark:text-orange-400', 'gray' => 'text-gray-400 dark:text-gray-500',
    ];
    $classes = $plain ? ($plainText[$color] ?? $plainText['gray']) : ($palette[$color] ?? $palette['gray']);
@endphp

<span {{ $attributes->merge(['class' => $plain
    ? "text-sm font-semibold whitespace-nowrap $classes"
    : "inline-flex items-center gap-1.5 text-xs px-2.5 py-0.5 rounded-full font-semibold whitespace-nowrap $classes"]) }}>
    @if($icon)
        <i class="bx {{ $icon }} text-xs"></i>
    @endif
    {{ $slot }}
</span>

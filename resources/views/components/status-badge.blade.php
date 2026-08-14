@props(['color' => 'gray', 'dot' => false, 'pulse' => false, 'icon' => null])

@php
    // One canonical pill shape/palette for every status/state indicator in the app — index
    // pages had drifted into three different treatments (pill+dot, flat rounded-md badge,
    // rounded select-lookalike) for the same concept before this existed.
    $palette = [
        'green' => 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-900/50',
        'amber' => 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-900/50',
        'red' => 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-900/50',
        'blue' => 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-900/50',
        'orange' => 'text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-900/50',
        'gray' => 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800/60 border-gray-200 dark:border-gray-700',
    ];
    $dotColor = [
        'green' => 'bg-green-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500',
        'blue' => 'bg-blue-500', 'orange' => 'bg-orange-500', 'gray' => 'bg-gray-400',
    ];
    $classes = $palette[$color] ?? $palette['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-[10px] uppercase tracking-widest px-3 py-1 rounded-full border font-bold whitespace-nowrap $classes"]) }}>
    @if($icon)
        <i class="bx {{ $icon }} text-xs"></i>
    @elseif($dot)
        <span class="w-2 h-2 rounded-full shrink-0 {{ $dotColor[$color] ?? $dotColor['gray'] }} {{ $pulse ? 'animate-pulse' : '' }}"></span>
    @endif
    {{ $slot }}
</span>

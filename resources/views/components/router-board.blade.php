@props(['portCount' => 5, 'sfpCount' => 0, 'status' => 'offline', 'compact' => false, 'image' => null])

@php
    $ledColor = match($status) {
        'online', 'active' => 'bg-green-500',
        'pending', 'provisioning' => 'bg-amber-500',
        default => 'bg-red-500',
    };
    $blinkSpeed = match($status) {
        'online', 'active' => '1s',
        'pending', 'provisioning' => '1.8s',
        default => null, // offline = solid, no blink
    };
@endphp

<div {{ $attributes->merge(['class' => 'inline-block']) }}>
    {{-- Real product photo, hotlinked from MikroTik's official CDN. The uplink status LED
         is overlaid on the photo itself rather than a separate port diagram — we don't have
         per-model port coordinates to place it on the correct physical port precisely. --}}
    <div class="relative shrink-0 {{ $compact ? 'w-10 h-10' : 'w-32 h-32' }} bg-white dark:bg-gray-100 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden mx-auto">
        @if ($image)
            <img src="{{ $image }}" loading="lazy" alt="MikroTik board photo" class="w-full h-full object-contain p-2">
        @else
            <i class='bx bx-router text-gray-300 {{ $compact ? "text-xl" : "text-5xl" }}'></i>
        @endif
        <span class="router-board-led absolute {{ $compact ? '-top-0.5 -right-0.5 w-2 h-2' : '-top-1 -right-1 w-3.5 h-3.5' }} rounded-full {{ $ledColor }} ring-2 ring-white dark:ring-gray-950"
              @if($blinkSpeed) style="animation: router-board-blink {{ $blinkSpeed }} steps(1) infinite;" @endif
              title="Uplink status"></span>
    </div>
    @unless($compact)
        <p class="text-[10px] text-gray-400 text-center mt-1 uppercase tracking-wide">{{ ucfirst($status) }}</p>
    @endunless
</div>

@once
    <style>
        @keyframes router-board-blink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.15; }
        }
    </style>
@endonce

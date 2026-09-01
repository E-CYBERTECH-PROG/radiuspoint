@props(['portCount' => 5, 'sfpCount' => 0, 'status' => 'offline', 'compact' => false, 'image' => null])

@php
    $ledColor = match($status) {
        'online', 'active' => 'bg-green',
        'pending', 'provisioning' => 'bg-yellow',
        default => 'bg-red',
    };
    $blinkSpeed = match($status) {
        'online', 'active' => '1s',
        'pending', 'provisioning' => '1.8s',
        default => null, // offline = solid, no blink
    };
@endphp

<div {{ $attributes->merge(['class' => 'd-inline-block']) }}>
    {{-- Real product photo, hotlinked from MikroTik's official CDN. The uplink status LED
         is overlaid on the photo itself rather than a separate port diagram — we don't have
         per-model port coordinates to place it on the correct physical port precisely. --}}
    <div class="router-board-photo {{ $compact ? 'router-board-photo-sm' : '' }} position-relative bg-white border rounded d-flex align-items-center justify-content-center overflow-hidden mx-auto">
        @if ($image)
            <img src="{{ $image }}" loading="lazy" alt="MikroTik board photo" class="w-100 h-100 p-2" style="object-fit:contain">
        @else
            <i class="ti ti-router text-muted {{ $compact ? 'fs-3' : '' }}" style="{{ $compact ? '' : 'font-size:3rem' }}"></i>
        @endif
        <span class="router-board-led position-absolute rounded-circle {{ $ledColor }}"
              @if($blinkSpeed) style="animation: router-board-blink {{ $blinkSpeed }} steps(1) infinite;" @endif
              title="Uplink status"></span>
    </div>
    @unless($compact)
        <p class="text-muted text-center mt-1 mb-0 text-uppercase small">{{ ucfirst($status) }}</p>
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

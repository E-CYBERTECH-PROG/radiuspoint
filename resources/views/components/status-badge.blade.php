@props(['color' => 'gray', 'dot' => false, 'pulse' => false, 'icon' => null, 'plain' => false])

@php
    // Matches the one-isp reference: a plain light pill for state columns (Status, package
    // Active/Inactive) — and a bare bold colored text treatment (plain) for the
    // Online/Offline column, which isn't pilled there at all.
    $colorMap = [
        'green' => 'green', 'amber' => 'yellow', 'red' => 'red',
        'blue' => 'blue', 'orange' => 'orange', 'gray' => 'secondary',
    ];
    $tblrColor = $colorMap[$color] ?? 'secondary';
@endphp

@if($plain)
    <span {{ $attributes->merge(['class' => "fw-semibold text-nowrap text-{$tblrColor}"]) }}>
        @if($icon)<i class="ti {{ $icon }}"></i>@endif
        {{ $slot }}
    </span>
@else
    <span {{ $attributes->merge(['class' => "badge bg-{$tblrColor}-lt" . ($dot ? ' badge-pill' : '')]) }}>
        @if($dot)
            <span class="badge {{ $pulse ? 'badge-blink' : '' }} bg-{{ $tblrColor }} me-1" style="width:.5rem;height:.5rem;padding:0;"></span>
        @endif
        @if($icon)<i class="ti {{ $icon }} me-1"></i>@endif
        {{ $slot }}
    </span>
@endif

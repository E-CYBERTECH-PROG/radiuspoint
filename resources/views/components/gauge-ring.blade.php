{{--
    Circular utilization gauge — SVG ring with rounded stroke caps and a soft color glow, no
    chart library. Renders with the given static $percent/$value/$detail; for a live-polled
    ring, pass an $id and have the poller update the arc plus the text nodes:

        var arc = document.getElementById('{id}-arc');
        var circumference = parseFloat(arc.dataset.circumference);
        var clamped = Math.max(0, Math.min(100, percent));
        arc.style.strokeDashoffset = circumference - (clamped / 100) * circumference;
        document.getElementById('{id}-value').textContent = ...;
        document.getElementById('{id}-detail').textContent = ...;
--}}
@props([
    'percent' => 0,
    'value' => '',
    'color' => 'var(--tblr-primary)',
    'label' => '',
    'detail' => null,
    'size' => 96,
    'id' => null,
    'icon' => null,
])

@php
    $gaugeId = $id ?? 'gauge-' . \Illuminate\Support\Str::random(8);
    $strokeWidth = max(6, (int) round($size * 0.09));
    $center = $size / 2;
    $radius = $center - $strokeWidth / 2;
    $circumference = 2 * M_PI * $radius;
    $clamped = max(0, min(100, (float) $percent));
    $offset = $circumference - ($clamped / 100) * $circumference;
    $valueFontSize = max(13, round($size * 0.19));
@endphp

<div class="d-flex flex-column align-items-center gap-2">
    <div
        class="position-relative d-inline-flex flex-shrink-0"
        style="width:{{ $size }}px;height:{{ $size }}px;filter:drop-shadow(0 3px 8px color-mix(in srgb, {{ $color }} 30%, transparent))"
    >
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="position-absolute top-0 start-0">
            <circle
                cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}"
                fill="none" stroke="var(--tblr-border-color)" stroke-width="{{ $strokeWidth }}"
            />
            <circle
                id="{{ $gaugeId }}-arc"
                cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}"
                fill="none"
                stroke="{{ $color }}"
                stroke-width="{{ $strokeWidth }}"
                stroke-linecap="round"
                stroke-dasharray="{{ $circumference }}"
                stroke-dashoffset="{{ $offset }}"
                data-circumference="{{ $circumference }}"
                transform="rotate(-90 {{ $center }} {{ $center }})"
                style="transition: stroke-dashoffset .7s cubic-bezier(.4,0,.2,1)"
            ></circle>
        </svg>
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center lh-1">
            @if($icon)
                <i class="ti {{ $icon }} text-muted mb-1" style="font-size:.75rem;opacity:.7"></i>
            @endif
            <span id="{{ $gaugeId }}-value" class="font-monospace fw-bold text-body" style="font-size:{{ $valueFontSize }}px">{{ $value }}</span>
        </div>
    </div>
    <div class="text-center">
        <p class="text-muted text-uppercase mb-0" style="font-size:.625rem;letter-spacing:.06em;font-weight:600">{{ $label }}</p>
        @if($detail !== null)
            <p id="{{ $gaugeId }}-detail" class="text-muted font-monospace mb-0 mt-1" style="font-size:.625rem">{{ $detail }}</p>
        @endif
    </div>
</div>

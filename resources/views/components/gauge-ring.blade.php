{{--
    Circular utilization gauge (CSS conic-gradient, no chart library). Renders with the
    given static $percent/$value/$detail; for a live-polled ring, pass an $id and have the
    poller write into it directly:

        document.getElementById('{id}').style.background =
            `conic-gradient(var(--tblr-primary) ${percent * 3.6}deg, var(--tblr-border-color) 0deg)`;
        document.getElementById('{id}-value').textContent = ...;
        document.getElementById('{id}-detail').textContent = ...;
--}}
@props(['percent' => 0, 'value' => '', 'color' => 'var(--tblr-primary)', 'label' => '', 'detail' => null, 'size' => 96, 'id' => null])

<div class="d-flex flex-column align-items-center gap-1">
    <div id="{{ $id }}" class="rounded-circle flex-shrink-0" style="width:{{ $size }}px;height:{{ $size }}px;background:conic-gradient({{ $color }} {{ $percent * 3.6 }}deg, var(--tblr-border-color) 0deg)">
        <div class="rounded-circle bg-body d-flex align-items-center justify-content-center h-100" style="margin:7px;width:calc(100% - 14px);height:calc(100% - 14px)">
            <span id="{{ $id ? $id.'-value' : null }}" class="font-monospace fw-bold text-body small">{{ $value }}</span>
        </div>
    </div>
    <div class="text-center">
        <p class="text-muted text-uppercase mb-0" style="font-size:.625rem;letter-spacing:.05em">{{ $label }}</p>
        @if($detail !== null)
            <p id="{{ $id ? $id.'-detail' : null }}" class="text-muted font-monospace mb-0" style="font-size:.625rem">{{ $detail }}</p>
        @endif
    </div>
</div>

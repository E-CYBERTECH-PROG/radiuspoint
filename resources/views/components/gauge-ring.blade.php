{{-- Circular utilization gauge (CSS conic-gradient, no chart library). $percentExpr,
     $valueExpr and $detailExpr are raw Alpine expression strings (not Blade values) — the
     ring is driven by live polled data, e.g. percentExpr="cpuLoad"
     valueExpr="cpuLoad !== null ? cpuLoad + '%' : '—'". Keep $valueExpr short (it renders
     inside the ring) — use $detailExpr for a longer caption below the label. --}}
@props(['percentExpr', 'valueExpr', 'color' => '#2563eb', 'label' => '', 'detailExpr' => null, 'size' => 96])

<div class="flex flex-col items-center gap-1.5">
    <div class="relative rounded-full shrink-0"
         :style="`width:{{ $size }}px;height:{{ $size }}px;background:conic-gradient({{ $color }} ${(({{ $percentExpr }}) ?? 0) * 3.6}deg, rgba(148,163,184,.35) 0deg)`">
        <div class="absolute inset-[7px] rounded-full bg-white dark:bg-gray-950 flex items-center justify-center">
            <span class="font-fira font-bold text-gray-900 dark:text-white text-sm" x-text="{{ $valueExpr }}"></span>
        </div>
    </div>
    <div class="text-center">
        <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $label }}</p>
        @if($detailExpr)
            <p class="text-[10px] text-gray-400 font-fira" x-text="{{ $detailExpr }}"></p>
        @endif
    </div>
</div>

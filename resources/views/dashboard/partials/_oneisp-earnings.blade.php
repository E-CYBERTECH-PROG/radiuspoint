{{-- Expects $stats/$currency/$incomeMonthDelta in scope. --}}
@php
    $oneispDelta = $incomeMonthDelta ?? 0;
    $oneispIsUp = $oneispDelta >= 0;
    // SVG ring: circumference for r=15.5 is ~97.4; offset shrinks as |delta| grows toward 100%.
    $oneispRingPct = max(0, min(100, abs($oneispDelta)));
    $oneispRingCircumference = 97.4;
    $oneispRingOffset = $oneispRingCircumference - ($oneispRingPct / 100 * $oneispRingCircumference);
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
        <div class="min-w-0">
            <p class="text-uppercase text-muted small fw-bold mb-0">Earnings</p>
            <p class="text-muted mt-2 mb-0" style="font-size:.6875rem">This Month</p>
            <p class="fs-5 font-monospace fw-bold text-truncate mb-0">{{ $currency }} {{ number_format($stats['income_month'] ?? 0) }}</p>
        </div>

        <div class="position-relative flex-shrink-0" style="width:4rem;height:4rem">
            <svg viewBox="0 0 36 36" style="width:4rem;height:4rem;transform:rotate(-90deg)">
                <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--tblr-border-color)" stroke-width="3"></circle>
                <circle cx="18" cy="18" r="15.5" fill="none"
                        stroke="{{ $oneispIsUp ? 'var(--tblr-green)' : 'var(--tblr-red)' }}"
                        stroke-width="3" stroke-linecap="round"
                        stroke-dasharray="{{ $oneispRingCircumference }}"
                        stroke-dashoffset="{{ $oneispRingOffset }}"></circle>
            </svg>
            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                <span class="font-monospace fw-bold {{ $oneispIsUp ? 'text-success' : 'text-danger' }}" style="font-size:.6875rem">{{ $oneispIsUp ? '+' : '-' }}{{ number_format(abs($oneispDelta), 1) }}%</span>
                <span class="text-muted text-uppercase" style="font-size:.5rem">Profit</span>
            </div>
        </div>
    </div>
</div>

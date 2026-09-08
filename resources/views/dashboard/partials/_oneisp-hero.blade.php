{{-- Expects $stats/$currency in scope, plus an optional $delay (ms). --}}
@php
    $oneispAmount = $currency.' '.number_format($stats['income_today'] ?? 0, 1);
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body d-flex flex-column">
        {{-- Live clock, not a static "Good morning" — a page loaded once and left open all day
             would otherwise keep saying "morning" at 4pm. Day/date left, time right (rather
             than stacked) reads more like a clean status bar than a wall clock. See rp-clock.js. --}}
        <div class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2">
            <div class="d-flex align-items-center gap-2 text-muted">
                <i class="ti ti-calendar-event" style="font-size:1rem"></i>
                <span class="small fw-bold" id="rp-hero-date">--</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-clock text-muted" style="font-size:1rem"></i>
                <span class="font-monospace fw-bold" id="rp-hero-clock" style="font-size:1rem">--:--:--</span>
            </div>
        </div>

        {{-- Card-face treatment for the actual figure — closer to a physical payment card than
             a plain stat line, which is also what makes "tap the figure to reveal it" read as a
             natural gesture instead of needing a separate icon to explain it. Always starts
             hidden — no persisted "leave it revealed" choice — since a screen someone glances
             over your shoulder at shouldn't depend on remembering to hide it again first. The
             masked state is a shimmering redacted bar (see rp-money-masked in _custom.scss)
             rather than a row of literal bullet characters. --}}
        <button type="button" class="rp-money-toggle border-0 rounded-3 text-start p-3 mt-3 w-100 overflow-hidden position-relative" style="background:linear-gradient(135deg, var(--tblr-primary), color-mix(in srgb, var(--tblr-primary) 70%, #6c2bd9)); max-width: 20rem" aria-label="Tap to show or hide today's earnings">
            <span class="d-block text-uppercase fw-bold" style="font-size:.65rem;letter-spacing:.08em;color:rgba(255,255,255,.75)">Today's Earnings</span>
            <span class="d-flex align-items-center font-monospace fw-bold text-white mt-2" style="font-size:1.5rem;letter-spacing:.03em;height:1.9rem">
                <span class="rp-money-masked rp-money-masked-light"></span>
                <span class="rp-money-value text-truncate">{{ $oneispAmount }}</span>
            </span>
            <span class="mt-2 rp-money-hint rp-money-hint-off" style="font-size:.65rem;color:rgba(255,255,255,.65)">Tap to reveal</span>
            <span class="mt-2 rp-money-hint rp-money-hint-on" style="font-size:.65rem;color:rgba(255,255,255,.65)">Tap to hide</span>
        </button>

        <a href="{{ route('reports.receipts') }}" class="btn btn-primary btn-sm mt-3 align-self-start">View Payments</a>
    </div>
</div>

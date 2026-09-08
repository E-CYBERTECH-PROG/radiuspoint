{{-- Expects $stats/$currency in scope, plus an optional $delay (ms). --}}
@php
    $oneispAmount = $currency.' '.number_format($stats['income_today'] ?? 0, 1);
    // Masked length roughly tracks the real value's length instead of a fixed placeholder, so
    // the card doesn't visibly resize the moment an admin reveals a much longer/shorter figure.
    $oneispMask = str_repeat('•', max(4, min(10, strlen($oneispAmount) - 4)));
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body d-flex flex-column">
        {{-- Live clock, not a static "Good morning" — a page loaded once and left open all day
             would otherwise keep saying "morning" at 4pm. See rp-clock.js. --}}
        <p class="fw-bold mb-0 font-monospace" id="rp-hero-clock" style="font-size:1.25rem">--:--:--</p>
        <p class="text-muted mb-0" style="font-size:.75rem" id="rp-hero-date"></p>

        {{-- Card-face treatment for the actual figure — closer to a physical payment card than
             a plain stat line, which is also what makes "tap the number to reveal it" read as a
             natural gesture instead of needing a separate icon to explain it. Hidden by default
             (see rp-privacy.js and the <html> class applied in layouts/sidebar.blade.php) —
             revenue shouldn't be the first thing visible on a screen someone glances over your
             shoulder at. --}}
        <button type="button" class="rp-money-toggle border-0 rounded-3 text-start p-3 mt-3 w-100 overflow-hidden position-relative" style="background:linear-gradient(135deg, var(--tblr-primary), color-mix(in srgb, var(--tblr-primary) 70%, #6c2bd9)); max-width: 20rem" aria-label="Tap to show or hide today's earnings">
            <span class="d-block text-uppercase fw-bold" style="font-size:.65rem;letter-spacing:.08em;color:rgba(255,255,255,.75)">Today's Earnings</span>
            <span class="d-block font-monospace fw-bold text-white text-truncate mt-1" style="font-size:1.5rem;letter-spacing:.03em">
                <span class="rp-money-masked">{{ $currency }} {{ $oneispMask }}</span>
                <span class="rp-money-value">{{ $oneispAmount }}</span>
            </span>
            <span class="mt-1 rp-money-hint rp-money-hint-off" style="font-size:.65rem;color:rgba(255,255,255,.65)">Tap to reveal</span>
            <span class="mt-1 rp-money-hint rp-money-hint-on" style="font-size:.65rem;color:rgba(255,255,255,.65)">Tap to hide</span>
        </button>

        <a href="{{ route('reports.receipts') }}" class="btn btn-primary btn-sm mt-3 align-self-start">View Payments</a>
    </div>
</div>

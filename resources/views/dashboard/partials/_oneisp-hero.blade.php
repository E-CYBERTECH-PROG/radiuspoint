{{-- Expects $stats/$currency in scope, plus an optional $delay (ms). --}}
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body position-relative overflow-hidden">
        <div class="position-absolute bg-primary-lt rounded-circle" style="width:10rem;height:10rem;right:-2.5rem;bottom:-3.5rem"></div>

        <div class="position-relative d-flex align-items-center justify-content-between gap-3">
            <div class="min-w-0">
                {{-- Live clock, not a static "Good morning" — a page loaded once and left open
                     all day would otherwise keep saying "morning" at 4pm. See rp-clock.js. --}}
                <p class="fw-bold mb-0 font-monospace" id="rp-hero-clock" style="font-size:1.25rem">--:--:--</p>
                <p class="text-muted mb-0" style="font-size:.75rem" id="rp-hero-date"></p>
                <div class="d-flex align-items-center gap-2 mt-3 mb-1">
                    <p class="text-uppercase text-muted small fw-bold mb-0">Today's Earnings</p>
                    {{-- Toggles .rp-money-hidden on <html> — see rp-privacy.js. Every money
                         value on the page (not just this card) responds to the same switch. --}}
                    <button type="button" class="rp-money-toggle text-muted p-0 border-0 bg-transparent lh-1" title="Hide/show amounts" aria-label="Hide or show money amounts">
                        <i class="ti ti-eye icon" style="font-size:1rem"></i>
                        <i class="ti ti-eye-off icon" style="font-size:1rem"></i>
                    </button>
                </div>
                <p class="fs-2 font-monospace fw-bold text-primary mb-3 rp-money">{{ $currency }} {{ number_format($stats['income_today'] ?? 0, 1) }}</p>
                <a href="{{ route('reports.receipts') }}" class="btn btn-primary btn-sm">View Payments</a>
            </div>

            {{-- Downloaded and resized locally (public/images/dashboard/trophy.png) rather than
                 hotlinked — same reasoning as the router board photos: an external image can
                 disappear if that host is slow/unreachable. --}}
            <div class="flex-shrink-0" style="width:6.5rem;margin-bottom:-1.25rem;margin-right:-.5rem" aria-hidden="true">
                <img src="{{ asset('images/dashboard/trophy.png') }}" alt="" class="w-100 h-auto d-block" style="filter:drop-shadow(0 8px 16px rgba(0,0,0,.15))">
            </div>
        </div>
    </div>
</div>

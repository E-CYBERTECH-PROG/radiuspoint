{{-- Expects $stats/$currency in scope, plus an optional $delay (ms). --}}
@php
    $oneispHour = now()->hour;
    $oneispGreeting = $oneispHour < 12 ? 'Good morning' : ($oneispHour < 17 ? 'Good afternoon' : 'Good evening');
    $oneispFirstName = explode(' ', Auth::user()->name)[0] ?? Auth::user()->name;
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body position-relative overflow-hidden">
        <div class="position-absolute bg-primary-lt rounded-circle" style="width:8rem;height:8rem;right:-1.5rem;top:-1.5rem"></div>

        <div class="position-relative d-flex align-items-start justify-content-between gap-3">
            <div>
                <p class="fw-bold mb-0">{{ $oneispGreeting }}, {{ $oneispFirstName }}!</p>
                <p class="text-uppercase text-muted small fw-bold mt-3 mb-1">Today's Earnings</p>
                <p class="fs-2 font-monospace fw-bold text-primary mb-0">{{ $currency }} {{ number_format($stats['income_today'] ?? 0, 1) }}</p>
            </div>

            <div class="flex-shrink-0" aria-hidden="true">
                <span class="avatar bg-yellow-lt">
                    <i class="ti ti-medal fs-3"></i>
                </span>
            </div>
        </div>
    </div>
</div>

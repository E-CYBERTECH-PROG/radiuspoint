{{-- Expects $stats/$currency in scope, plus an optional $delay (ms). --}}
@php
    $oneispHour = now()->hour;
    $oneispGreeting = $oneispHour < 12 ? 'Good morning' : ($oneispHour < 17 ? 'Good afternoon' : 'Good evening');
    $oneispFirstName = explode(' ', Auth::user()->name)[0] ?? Auth::user()->name;
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body position-relative overflow-hidden">
        <div class="position-absolute bg-primary-lt rounded-circle" style="width:10rem;height:10rem;right:-2.5rem;bottom:-3.5rem"></div>

        <div class="position-relative d-flex align-items-center justify-content-between gap-3">
            <div class="min-w-0">
                <p class="fw-bold mb-0">{{ $oneispGreeting }}, {{ $oneispFirstName }}!</p>
                <p class="text-uppercase text-muted small fw-bold mt-3 mb-1">Today's Earnings</p>
                <p class="fs-2 font-monospace fw-bold text-primary mb-3">{{ $currency }} {{ number_format($stats['income_today'] ?? 0, 1) }}</p>
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

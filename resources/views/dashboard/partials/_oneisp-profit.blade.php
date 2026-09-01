{{-- Expects $stats/$currency in scope. Canvas #oneispProfitChart initialized in _oneisp-scripts.blade.php. --}}
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body p-3">
        <p class="text-uppercase text-muted small fw-bold mb-1">Profit</p>
        <p class="fs-4 font-monospace fw-bold mb-2 text-truncate">{{ $currency }} {{ number_format($stats['income_month'] ?? 0) }}</p>
        <div style="height:2.5rem">
            <canvas id="oneispProfitChart"></canvas>
        </div>
    </div>
</div>

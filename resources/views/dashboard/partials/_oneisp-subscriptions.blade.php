{{-- Expects $subscriptionsThisMonth in scope. Canvas #oneispSubscriptionsChart initialized in _oneisp-scripts.blade.php. --}}
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body p-3">
        <p class="text-uppercase text-muted small fw-bold mb-1">Subscriptions</p>
        <p class="fs-4 font-monospace fw-bold mb-2">{{ number_format($subscriptionsThisMonth) }}</p>
        <div style="height:2.5rem">
            <canvas id="oneispSubscriptionsChart"></canvas>
        </div>
    </div>
</div>

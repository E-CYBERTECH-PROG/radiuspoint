{{-- Canvas #oneispRevenueChart initialized in _oneisp-scripts.blade.php.
     Expense is always 0 — this app doesn't have expense tracking yet, so the legend
     reflects that honestly rather than fabricating a number.

     No own card chrome — this shares a single card with _oneisp-side-chart via the
     wrapper in oneisp.blade.php. --}}
<div class="card-body d-flex flex-column h-100">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="card-title mb-0">Revenue Report</h3>
        <div class="d-flex align-items-center gap-3">
            <span class="d-flex align-items-center gap-1 text-muted small fw-bold"><span class="badge bg-primary" style="width:.5rem;height:.5rem;padding:0"></span> Earning</span>
            <span class="d-flex align-items-center gap-1 text-muted small fw-bold"><span class="badge bg-yellow" style="width:.5rem;height:.5rem;padding:0"></span> Expense</span>
        </div>
    </div>
    <div class="position-relative flex-fill" style="min-height:16rem">
        <canvas id="oneispRevenueChart"></canvas>
    </div>
</div>

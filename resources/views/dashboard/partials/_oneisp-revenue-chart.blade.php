{{-- Canvas #oneispRevenueChart initialized in _oneisp-scripts.blade.php.
     Expense is always 0 — this app doesn't have expense tracking yet, so the legend
     reflects that honestly rather than fabricating a number.

     No own card chrome — this shares a single card with _oneisp-side-chart via the
     wrapper in oneisp.blade.php. --}}
<div class="card-body d-flex flex-column h-100">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h3 class="card-title mb-0">Revenue Report</h3>
        <div class="d-flex align-items-center gap-3">
            <span class="d-flex align-items-center gap-1 text-muted small fw-bold"><span class="badge bg-primary" style="width:.5rem;height:.5rem;padding:0"></span> Earning</span>
            <span class="d-flex align-items-center gap-1 text-muted small fw-bold"><span class="badge bg-orange" style="width:.5rem;height:.5rem;padding:0"></span> Expense</span>
            {{-- Fetched via /dashboard/revenue-chart and swapped into the Chart.js instance in
                 place (see _oneisp-scripts.blade.php) — no page reload. --}}
            <select id="oneispRevenueRange" class="form-select form-select-sm w-auto">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month" selected>This Month</option>
                <option value="this_year">This Year</option>
                <option value="last_year">Last Year</option>
            </select>
        </div>
    </div>
    <div class="position-relative flex-fill" style="min-height:16rem">
        <canvas id="oneispRevenueChart"></canvas>
    </div>
</div>

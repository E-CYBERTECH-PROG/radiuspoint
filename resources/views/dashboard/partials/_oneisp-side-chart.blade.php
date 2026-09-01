{{-- Expects $growthLabels/$growthData in scope. Canvas #oneispSideChart initialized in _oneisp-scripts.blade.php.
     "Add Expense" is inert — there's no expense feature in this app yet, kept as a visual
     element to match the reference design rather than wired to a fake action.

     No own card chrome — this shares a single card with _oneisp-revenue-chart via the
     wrapper in oneisp.blade.php. --}}
<div class="card-body d-flex flex-column h-100">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="text-uppercase text-muted small fw-bold">New Customers</span>
        <span class="d-inline-flex align-items-center gap-1 border rounded px-2 py-1 text-muted small fw-bold">
            {{ now()->year }} <i class="ti ti-chevron-down"></i>
        </span>
    </div>
    <div class="position-relative flex-fill" style="min-height:7rem">
        <canvas id="oneispSideChart"></canvas>
    </div>
    <button type="button" disabled title="Expense tracking isn't available yet" class="btn btn-primary w-100 mt-3">
        Add Expense
    </button>
</div>

{{-- Expects $packageBreakdown/$currency in scope. Canvas #oneispPackagePerformanceChart
     initialized in _oneisp-scripts.blade.php. Slice colors here must stay in the same order
     as $oneispPackageColors in that file, since the legend below is built independently
     from the chart itself (no Chart.js legend plugin) but needs to match its colors. --}}
@php
    $oneispPackageColors = ['#6366f1', '#8b5cf6', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#14b8a6'];
    $oneispTotalRevenue = $packageBreakdown->sum('revenue');
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body d-flex flex-column">
        <h3 class="card-title mb-1">Package Performance</h3>
        <p class="text-muted small mb-3">By revenue share, this month</p>

        @if($packageBreakdown->isEmpty())
            <p class="text-center text-muted text-uppercase small py-5 mb-0">No package sales this month.</p>
        @else
            <div class="position-relative flex-shrink-0" style="height:9rem">
                <canvas id="oneispPackagePerformanceChart"></canvas>
            </div>

            <div class="mt-3 d-flex flex-column gap-2 custom-scrollbar" style="max-height:12rem;overflow-y:auto">
                @foreach($packageBreakdown as $i => $row)
                    <div class="d-flex align-items-center justify-content-between gap-2 small">
                        <span class="d-flex align-items-center gap-2 min-w-0">
                            <span class="rounded-circle flex-shrink-0" style="width:.5rem;height:.5rem;background-color: {{ $oneispPackageColors[$i % count($oneispPackageColors)] }}"></span>
                            <span class="text-muted text-truncate">{{ $row->package_name }}</span>
                        </span>
                        <span class="fw-bold flex-shrink-0">{{ $oneispTotalRevenue > 0 ? round($row->revenue / $oneispTotalRevenue * 100) : 0 }}%</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

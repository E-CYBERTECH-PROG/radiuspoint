{{-- Expects $packageBreakdown/$currency in scope. Canvas #oneispPackagePerformanceChart
     initialized in _oneisp-scripts.blade.php. Slice colors here must stay in the same order
     as $oneispPackageColors in that file, since the legend below is built independently
     from the chart itself (no Chart.js legend plugin) but needs to match its colors. --}}
@php
    $oneispPackageColors = ['#6366f1', '#8b5cf6', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#14b8a6'];
    $oneispTotalRevenue = $packageBreakdown->sum('revenue');
@endphp
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-5 h-full flex flex-col rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Package Performance</h3>
    <p class="text-[11px] text-gray-400 mb-3">By revenue share, this month</p>

    @if($packageBreakdown->isEmpty())
        <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-16">No package sales this month.</p>
    @else
        <div class="relative h-36 w-full shrink-0">
            <canvas id="oneispPackagePerformanceChart"></canvas>
        </div>

        <div class="mt-4 space-y-2 overflow-y-auto custom-scrollbar">
            @foreach($packageBreakdown as $i => $row)
                <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $oneispPackageColors[$i % count($oneispPackageColors)] }}"></span>
                        <span class="text-gray-600 dark:text-gray-300 truncate">{{ $row->package_name }}</span>
                    </span>
                    <span class="font-bold text-gray-900 dark:text-white shrink-0">{{ $oneispTotalRevenue > 0 ? round($row->revenue / $oneispTotalRevenue * 100) : 0 }}%</span>
                </div>
            @endforeach
        </div>
    @endif
</div>

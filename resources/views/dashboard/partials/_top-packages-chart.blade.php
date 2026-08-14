{{-- Expects $topPackages in scope. Canvas #topPackagesChart initialized in _scripts.blade.php. --}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm h-full rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Packages</h3>
        <a href="{{ route('reports.analytics') }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">Full Report</a>
    </div>
    @if($topPackages->isNotEmpty())
        <div class="relative h-56 w-full">
            <canvas id="topPackagesChart"></canvas>
        </div>
    @else
        <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-16">No package sales yet.</p>
    @endif
</div>

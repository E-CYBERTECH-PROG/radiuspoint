{{-- Expects $growthData in scope. Canvas #growthChart initialized in _scripts.blade.php,
     guarded by the same array_sum($growthData) > 0 check used there. --}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm h-full rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Customer Growth</h3>
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last 6 Months</span>
    </div>
    @if(array_sum($growthData) > 0)
        <div class="relative h-56 w-full">
            <canvas id="growthChart"></canvas>
        </div>
    @else
        <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-16">No new customers yet.</p>
    @endif
</div>

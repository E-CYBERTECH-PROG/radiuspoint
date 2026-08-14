{{-- Canvas #revenueChart is initialized once in dashboard/partials/_scripts.blade.php. --}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm flex flex-col h-full rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Revenue Collections</h3>
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last 6 Months</span>
    </div>
    <div class="relative h-72 w-full">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

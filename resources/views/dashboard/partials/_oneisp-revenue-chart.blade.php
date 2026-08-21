{{-- Canvas #oneispRevenueChart initialized in _oneisp-scripts.blade.php.
     Expense is always 0 — this app doesn't have expense tracking yet, so the legend
     reflects that honestly rather than fabricating a number.

     No own card chrome (bg/border/rounded/shadow) — this shares a single card with
     _oneisp-side-chart via the wrapper in oneisp.blade.php. --}}
<div class="p-5 h-full flex flex-col">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Revenue Report</h3>
        <div class="flex items-center gap-4">
            <span class="flex items-center gap-1.5 text-[11px] font-bold text-gray-500 dark:text-gray-400"><span class="w-2 h-2 rounded-full bg-indigo-500"></span> Earning</span>
            <span class="flex items-center gap-1.5 text-[11px] font-bold text-gray-500 dark:text-gray-400"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Expense</span>
        </div>
    </div>
    <div class="relative flex-1 min-h-[16rem] w-full">
        <canvas id="oneispRevenueChart"></canvas>
    </div>
</div>

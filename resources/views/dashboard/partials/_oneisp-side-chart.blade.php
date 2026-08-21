{{-- Expects $growthLabels/$growthData in scope. Canvas #oneispSideChart initialized in _oneisp-scripts.blade.php.
     "Add Expense" is inert — there's no expense feature in this app yet, kept as a visual
     element to match the reference design rather than wired to a fake action.

     No own card chrome (bg/border/rounded/shadow) — this shares a single card with
     _oneisp-revenue-chart via the wrapper in oneisp.blade.php. --}}
<div class="p-5 h-full flex flex-col">
    <div class="flex items-center justify-between mb-4">
        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">New Customers</span>
        <span class="inline-flex items-center gap-1 border border-gray-200 dark:border-gray-700 rounded-md px-2 py-1 text-[11px] font-bold text-gray-600 dark:text-gray-300">
            {{ now()->year }} <i class="bx bx-chevron-down text-sm"></i>
        </span>
    </div>
    <div class="relative flex-1 min-h-[7rem] w-full">
        <canvas id="oneispSideChart"></canvas>
    </div>
    <button type="button" disabled title="Expense tracking isn't available yet" class="mt-4 w-full bg-indigo-600 text-white text-sm font-bold py-2.5 rounded-lg opacity-90 cursor-not-allowed">
        Add Expense
    </button>
</div>

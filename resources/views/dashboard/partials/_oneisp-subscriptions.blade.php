{{-- Expects $subscriptionsThisMonth in scope. Canvas #oneispSubscriptionsChart initialized in _oneisp-scripts.blade.php. --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-4 h-full rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Subscriptions</p>
    <p class="text-xl font-fira font-extrabold text-gray-900 dark:text-white mt-1 mb-2">{{ number_format($subscriptionsThisMonth) }}</p>
    <div class="h-10 w-full">
        <canvas id="oneispSubscriptionsChart"></canvas>
    </div>
</div>

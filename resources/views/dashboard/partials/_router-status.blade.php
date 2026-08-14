{{-- Expects a live-updating `routerList` on the closest x-data="dashboard(...)" ancestor. --}}
<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm flex flex-col h-full rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Router Status</h3>
        <a href="{{ route('routers.index') }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
    </div>
    <div class="flex-1 space-y-1">
        <template x-if="routerList.length === 0">
            <p class="text-sm text-gray-500 py-6 text-center">No routers deployed yet.</p>
        </template>
        <template x-for="(router, i) in routerList" :key="i">
            <div class="flex items-center justify-between py-2.5 px-2 -mx-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full" :class="router.status === 'active' ? 'bg-green-500 animate-pulse' : (router.status === 'offline' ? 'bg-red-500' : 'bg-amber-500')"></span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="router.name"></span>
                </div>
                <span class="text-xs text-gray-500 uppercase tracking-wide" x-text="router.status"></span>
            </div>
        </template>
    </div>
</div>

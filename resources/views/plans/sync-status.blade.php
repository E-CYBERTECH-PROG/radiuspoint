<x-sidebar-layout title="Sync Status — {{ $plan->name }}">
    <div class="mb-6">
        <a href="{{ route('plans.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to Plans
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Per-router sync status — updated automatically every minute by the background reconciler, no manual re-sync needed.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Router</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Message</th>
                        <th class="px-6 py-4 text-right">Last Synced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($routers as $router)
                        @php $sync = $syncs->get($router->id); @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">{{ $router->name }}</td>
                            <td class="px-6 py-4 text-center">
                                @if(! $sync)
                                    <span class="inline-flex items-center gap-1.5 text-[10px] text-gray-500 uppercase tracking-widest bg-gray-50 dark:bg-gray-900 px-3 py-1 rounded-full border border-gray-200 dark:border-gray-800 font-bold">
                                        <i class="bx bx-time"></i> Awaiting first sync
                                    </span>
                                @elseif($sync->status === 'synced')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] text-green-700 dark:text-green-400 uppercase tracking-widest bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full border border-green-200 dark:border-green-900/50 font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Synced
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[10px] text-red-700 dark:text-red-400 uppercase tracking-widest bg-red-50 dark:bg-red-900/20 px-3 py-1 rounded-full border border-red-200 dark:border-red-900/50 font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Failed
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $sync->message ?? '—' }}</td>
                            <td class="px-6 py-4 text-right text-xs text-gray-500">{{ $sync?->synced_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-router text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No active routers to sync to.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-sidebar-layout>

<x-sidebar-layout title="Plans">
    <div x-data="{ selected: [] }">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Packages &amp; Plans</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your plan lineup, and how well each one has synced to the routers running it.</p>
            </div>
            <div class="flex items-center gap-3">
                <form action="{{ route('plans.destroy-bulk') }}" method="POST" x-show="selected.length > 0" x-cloak
                      onsubmit="return confirm('Delete the selected plan(s)? Any still assigned to a customer will be skipped, not deleted.')">
                    @csrf @method('DELETE')
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="plan_ids[]" :value="id">
                    </template>
                    <button type="submit" class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 font-bold text-sm py-2.5 px-5 rounded-lg border border-red-200 dark:border-red-900/50 transition-colors">
                        <i class='bx bx-trash text-lg'></i> Delete Selected (<span x-text="selected.length"></span>)
                    </button>
                </form>
                <a href="{{ route('plans.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    <i class='bx bx-plus text-lg'></i> Add New Plan
                </a>
            </div>
        </div>

        <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
            <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
                <i class="bx bx-search text-gray-400 text-lg"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by plan name..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
            </div>
            <select name="type" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                <option value="">All Types</option>
                <option value="pppoe" @selected(request('type') === 'pppoe')>PPPoE</option>
                <option value="hotspot" @selected(request('type') === 'hotspot')>Hotspot</option>
            </select>
            <x-per-page-select />
            <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
            @if(request()->hasAny(['search', 'type']))
                <a href="{{ route('plans.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-4 w-10">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       @change="selected = $event.target.checked ? [{{ $plans->pluck('id')->implode(',') }}] : []"
                                       :checked="selected.length === {{ $plans->count() }} && {{ $plans->count() }} > 0">
                            </th>
                            <th class="px-6 py-4">Plan Name</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Bandwidth (RX/TX)</th>
                            <th class="px-6 py-4">Validity</th>
                            <th class="px-6 py-4">Price</th>
                            <th class="px-6 py-4 text-center">Sync Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($plans as $plan)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-4">
                                    <input type="checkbox" value="{{ $plan->id }}" x-model="selected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">{{ $plan->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold {{ $plan->type == 'hotspot' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50' : 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50' }}">
                                        {{ $plan->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-blue-700 dark:text-blue-400 font-bold">
                                    {{ $plan->speed_limit }}
                                    @if($plan->data_cap_mb)
                                        <span class="block text-[10px] text-gray-400 font-normal normal-case">{{ number_format($plan->data_cap_mb) }} MB cap</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $plan->duration_value }} {{ ucfirst($plan->duration_unit) }}</td>
                                <td class="px-6 py-4 text-green-700 dark:text-green-400 font-bold">KES {{ number_format($plan->price) }}</td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $counts = $syncCounts[$plan->id] ?? collect();
                                        $synced = $counts->get('synced', 0);
                                        $failed = $counts->get('failed', 0);
                                    @endphp
                                    @if($activeRouterCount === 0)
                                        <span class="text-[10px] text-gray-400 uppercase tracking-widest">No routers</span>
                                    @elseif($failed > 0)
                                        <a href="{{ route('plans.sync-status', $plan) }}" class="inline-flex items-center gap-2 text-[10px] text-red-700 dark:text-red-400 uppercase tracking-widest font-bold hover:underline">
                                            <span class="w-2 h-2 rounded-full bg-red-500"></span> Sync issue
                                        </a>
                                    @elseif($synced >= $activeRouterCount)
                                        <a href="{{ route('plans.sync-status', $plan) }}" class="inline-flex items-center gap-2 text-[10px] text-green-700 dark:text-green-400 uppercase tracking-widest font-bold hover:underline">
                                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Synced
                                        </a>
                                    @else
                                        <a href="{{ route('plans.sync-status', $plan) }}" class="inline-flex items-center gap-2 text-[10px] text-amber-700 dark:text-amber-400 uppercase tracking-widest font-bold hover:underline">
                                            <i class="bx bx-loader-alt bx-spin"></i> Syncing...
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('plans.sync-status', $plan) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Sync Status"><i class="bx bx-git-compare text-lg"></i></a>
                                        <a href="{{ route('plans.edit', $plan) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit"><i class="bx bx-edit-alt text-lg"></i></a>
                                        <form action="{{ route('plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Delete this plan? Customers assigned to it must be reassigned first.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete"><i class="bx bx-trash text-lg"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                    <i class='bx bx-box text-4xl mb-3 text-gray-200'></i>
                                    <p class="text-xs tracking-widest uppercase">No plans yet — add your first one to get started.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $plans->links() }}</div>
    </div>
</x-sidebar-layout>

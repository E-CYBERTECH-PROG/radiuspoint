<x-sidebar-layout title="PPPoE Account Status">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">PPPoE Account Status &amp; Expiry</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No prepaid wallet balance is tracked — this is account status, not a monetary figure.</p>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <x-per-page-select />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search']))
            <a href="{{ route('reports.pppoe-balances') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Price</th>
                        <th class="px-6 py-4">Expiry</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold font-fira">{{ $user->username }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->name ?: '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $plans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="px-6 py-4 font-fira text-gray-900 dark:text-white">{{ isset($plans[$user->current_plan_id]) ? 'KES ' . number_format($plans[$user->current_plan_id]->price) : '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->expires_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                @if($user->status === 'active')
                                    <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50">Active</span>
                                @elseif($user->status === 'expired')
                                    <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50">Expired</span>
                                @else
                                    <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50">Offline</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-user text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No PPPoE accounts yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-sidebar-layout>

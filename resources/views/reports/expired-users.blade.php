<x-sidebar-layout title="Expired Users">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Expired Users</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Hotspot and PPPoE accounts past their expiry.</p>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or username..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <input type="date" name="to" value="{{ request('to') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <x-per-page-select />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search', 'from', 'to']))
            <a href="{{ route('reports.expired-users') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Service</th>
                        <th class="px-6 py-4">Identifier</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Router</th>
                        <th class="px-6 py-4 text-right">Expired</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4">
                                <x-status-badge color="{{ $user->type === 'hotspot' ? 'orange' : 'blue' }}">{{ ucfirst($user->type) }}</x-status-badge>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 dark:text-white font-fira">{{ $user->identifier }}</p>
                                @if($user->name)
                                    <p class="text-xs text-gray-500">{{ $user->name }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $plans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $routers[$user->current_router_id]->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-right text-xs text-gray-500">{{ $user->expires_at ? \Carbon\Carbon::parse($user->expires_at)->diffForHumans() : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-check-circle text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No expired users right now.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-sidebar-layout>

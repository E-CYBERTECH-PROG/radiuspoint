<x-sidebar-layout title="Access Requests">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Access Requests</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">RADIUS authentication sessions across your routers.</p>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <input type="date" name="from" value="{{ request('from') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <input type="date" name="to" value="{{ request('to') }}" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
        <x-per-page-select :default="50" />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search', 'from', 'to']))
            <a href="{{ route('reports.access-log') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Router</th>
                        <th class="px-6 py-4">Framed IP</th>
                        <th class="px-6 py-4">Session Start</th>
                        <th class="px-6 py-4">Duration</th>
                        <th class="px-6 py-4">Data Usage</th>
                        <th class="px-6 py-4 text-right">Terminate Cause</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold font-fira">{{ $log->username }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $routersByIp[$log->nasipaddress]->name ?? $log->nasipaddress }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-fira">{{ $log->framedipaddress ?: '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $log->acctstarttime ? \Carbon\Carbon::parse($log->acctstarttime)->format('d M Y H:i') : '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-fira">
                                @if($log->acctsessiontime)
                                    {{ sprintf('%02d:%02d:%02d', floor($log->acctsessiontime / 3600), floor(($log->acctsessiontime % 3600) / 60), $log->acctsessiontime % 60) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-fira">
                                {{ number_format((($log->acctinputoctets ?? 0) + ($log->acctoutputoctets ?? 0)) / 1048576, 1) }} MB
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-gray-500">{{ $log->acctterminatecause ?: 'Active' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-shield text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No access requests recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-sidebar-layout>

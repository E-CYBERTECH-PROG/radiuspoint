<x-sidebar-layout title="OLT Devices">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">OLT Devices</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Remote access to your GPON/EPON OLTs (VSOL, Hioso, and similar).</p>
        </div>
        <a href="{{ route('olts.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
            <i class="bx bx-plus text-lg"></i> Add OLT
        </a>
    </div>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Brand</th>
                        <th class="px-6 py-4">IP Address</th>
                        <th class="px-6 py-4">PON Ports</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Last Seen</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($olts as $olt)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">
                                <a href="{{ route('olts.show', $olt) }}" class="hover:text-blue-600 dark:hover:text-blue-400 hover:underline">{{ $olt->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 uppercase text-xs font-bold">{{ $olt->brand }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-fira">{{ $olt->ip_address }}:{{ $olt->ssh_port }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $olt->pon_ports ?: '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($olt->status === 'active')
                                    <x-status-badge color="green" dot>Active</x-status-badge>
                                @elseif($olt->status === 'offline')
                                    <x-status-badge color="red">Offline</x-status-badge>
                                @else
                                    <x-status-badge color="gray">Pending</x-status-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">{{ $olt->last_seen?->diffForHumans() ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('olts.show', $olt) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Open"><i class="bx bx-right-arrow-alt text-lg"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-git-repo-forked text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No OLTs added yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $olts->links() }}</div>
</x-sidebar-layout>

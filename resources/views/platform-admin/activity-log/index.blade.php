<x-sidebar-layout title="Activity Log">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Activity Log</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Audit trail of platform admin actions on tenant accounts.</p>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <select name="tenant_id" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none flex-1">
            <option value="">All Tenants</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->company_name }}</option>
            @endforeach
        </select>
        <select name="admin_user_id" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none flex-1">
            <option value="">All Admins</option>
            @foreach($admins as $admin)
                <option value="{{ $admin->id }}" @selected(request('admin_user_id') == $admin->id)>{{ $admin->name }}</option>
            @endforeach
        </select>
        <select name="action" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none flex-1">
            <option value="">All Actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['tenant_id', 'admin_user_id', 'action']))
            <a href="{{ route('platform-admin.activity-log.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">Admin</th>
                        <th class="px-6 py-4">Tenant</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">{{ $log->admin?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $log->tenant?->company_name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50">{{ str_replace('_', ' ', $log->action) }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $log->details ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-history text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No activity recorded yet.</p>
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

<x-sidebar-layout title="Tenants">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tenant Accounts</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage ISP companies using RadiusPoint.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('platform-admin.tenants.export', request()->query()) }}" class="inline-flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <i class="bx bx-download text-lg"></i> Export CSV
            </a>
            <a href="{{ route('platform-admin.tenants.import-form') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-upload text-lg"></i> Import Tenants
            </a>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company name..." class="bg-transparent border-none outline-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <select name="status" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
            <option value="">All Statuses</option>
            @foreach(['pending', 'active', 'suspended', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="tier" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
            <option value="">All Tiers</option>
            @foreach(['free', 'starter', 'pro'] as $tier)
                <option value="{{ $tier }}" @selected(request('tier') === $tier)>{{ ucfirst($tier) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search', 'status', 'tier']))
            <a href="{{ route('platform-admin.tenants.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Company</th>
                        <th class="px-6 py-4">Owner</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Subscription</th>
                        <th class="px-6 py-4">Signed Up</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($tenants as $tenant)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">{{ $tenant->company_name }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                @if($owner = $tenant->users->first())
                                    {{ $owner->name }}<br><span class="text-xs text-gray-400">{{ $owner->email }}</span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50',
                                        'active' => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50',
                                        'suspended' => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
                                        'rejected' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold border {{ $statusColors[$tenant->status] ?? '' }}">{{ $tenant->status }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50">{{ $tenant->subscription_tier }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $tenant->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View"><i class="bx bx-show text-lg"></i></a>
                                    @if($tenant->status === 'pending')
                                        <form action="{{ route('platform-admin.tenants.approve', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs text-green-600 hover:text-green-700 font-bold uppercase tracking-wide">Approve</button>
                                        </form>
                                        <form action="{{ route('platform-admin.tenants.reject', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-bold uppercase tracking-wide">Reject</button>
                                        </form>
                                    @elseif($tenant->status === 'active')
                                        <form action="{{ route('platform-admin.tenants.suspend', $tenant) }}" method="POST" onsubmit="return rpConfirm(event, 'Suspend this tenant?')">
                                            @csrf
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-bold uppercase tracking-wide">Suspend</button>
                                        </form>
                                    @elseif($tenant->status === 'suspended')
                                        <form action="{{ route('platform-admin.tenants.reactivate', $tenant) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs text-green-600 hover:text-green-700 font-bold uppercase tracking-wide">Reactivate</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-buildings text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No tenants match these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>
</x-sidebar-layout>

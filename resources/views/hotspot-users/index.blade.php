<x-sidebar-layout title="Hotspot Users">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hotspot Users</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Public WiFi customers identified by phone / MAC address.</p>
        </div>
        <a href="{{ route('hotspot-users.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
            <i class="bx bx-user-plus text-lg"></i> Add Customer
        </a>
    </div>

    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
            <i class="bx bx-search text-gray-400 text-lg"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or MAC..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
        </div>
        <select name="status" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
            <option value="">All Statuses</option>
            @foreach(['active', 'expired', 'offline'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="plan_id" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
            <option value="">All Packages</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
            @endforeach
        </select>
        <x-per-page-select />
        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
        @if(request()->hasAny(['search', 'status', 'plan_id']))
            <a href="{{ route('hotspot-users.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
        @endif
    </form>

    <div x-data="hotspotLiveStatus()" x-init="startPolling()" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Phone</th>
                        <th class="px-6 py-4">MAC Address</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Router</th>
                        <th class="px-6 py-4">Expiry</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Live</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-bold font-fira">{{ $user->phone_number }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-fira">{{ $user->mac_address ?: '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $plans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $routers[$user->current_router_id]->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->expires_at ? \Carbon\Carbon::parse($user->expires_at)->format('d M Y H:i') : '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($user->status === 'active')
                                    <span class="inline-flex items-center gap-1 text-[10px] text-green-700 dark:text-green-400 uppercase tracking-widest bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full border border-green-200 dark:border-green-900/50 font-bold">
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span> Active
                                    </span>
                                @elseif($user->status === 'expired')
                                    <span class="inline-flex items-center gap-1 text-[10px] text-amber-700 dark:text-amber-400 uppercase tracking-widest bg-amber-50 dark:bg-amber-900/20 px-3 py-1 rounded-full border border-amber-200 dark:border-amber-900/50 font-bold">
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] text-red-700 dark:text-red-400 uppercase tracking-widest bg-red-50 dark:bg-red-900/20 px-3 py-1 rounded-full border border-red-200 dark:border-red-900/50 font-bold">
                                        Offline
                                    </span>
                                @endif
                                @if($user->fup_throttled_at)
                                    <span class="block mt-1 inline-flex items-center gap-1 text-[10px] text-orange-700 dark:text-orange-400 uppercase tracking-widest font-bold" title="Throttled since {{ $user->fup_throttled_at->diffForHumans() }}">
                                        <i class="bx bx-tachometer"></i> FUP
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <template x-if="live['{{ $user->phone_number }}']?.online">
                                    <span class="inline-flex items-center gap-1 text-[10px] text-green-700 dark:text-green-400 uppercase tracking-widest bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full border border-green-200 dark:border-green-900/50 font-bold">
                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Online
                                    </span>
                                </template>
                                <template x-if="live['{{ $user->phone_number }}'] && !live['{{ $user->phone_number }}'].online">
                                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">Offline</span>
                                </template>
                                <template x-if="!live['{{ $user->phone_number }}']">
                                    <span class="text-[10px] text-gray-300 dark:text-gray-700">·</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if($user->current_router_id)
                                        <button type="button" x-show="live['{{ $user->phone_number }}']?.online" x-cloak
                                                @click="disconnect('{{ $user->phone_number }}', {{ $user->current_router_id }})"
                                                class="text-gray-400 hover:text-amber-600 transition-colors" title="Disconnect">
                                            <i class="bx bx-unlink text-lg"></i>
                                        </button>
                                        <button type="button" x-show="live['{{ $user->phone_number }}']?.online" x-cloak
                                                @click="block('{{ $user->phone_number }}', {{ $user->current_router_id }})"
                                                class="text-gray-400 hover:text-red-600 transition-colors" title="Block">
                                            <i class="bx bx-block text-lg"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('hotspot-users.edit', $user) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit"><i class="bx bx-edit-alt text-lg"></i></a>
                                    <form action="{{ route('hotspot-users.destroy', $user) }}" method="POST" onsubmit="return confirm('Remove this customer?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove"><i class="bx bx-trash text-lg"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-wifi text-4xl mb-3 text-gray-200"></i>
                                <p class="text-xs tracking-widest uppercase">No hotspot customers yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    <x-slot name="scripts">
        <script>
            function hotspotLiveStatus() {
                return {
                    live: {},
                    phoneNumbers: @json($users->pluck('phone_number')),
                    disconnectUrlTemplate: "{{ route('routers.actions.disconnect-hotspot', ['router' => '__ROUTER__']) }}",
                    blockUrlTemplate: "{{ route('routers.actions.block-hotspot', ['router' => '__ROUTER__']) }}",

                    startPolling() {
                        if (this.phoneNumbers.length === 0) return;
                        this.poll();
                        setInterval(() => this.poll(), 5000);
                    },

                    async poll() {
                        try {
                            const params = new URLSearchParams();
                            this.phoneNumbers.forEach(p => params.append('phone_numbers[]', p));
                            const res = await fetch(`{{ route('hotspot-users.live-status') }}?${params}`, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            this.live = json.data || {};
                        } catch (e) {
                            // Leave `live` as-is on a transient failure.
                        }
                    },

                    async disconnect(phone, routerId) {
                        const session = this.live[phone];
                        if (!session || !session.id) return;
                        if (!confirm(`Disconnect ${phone}?`)) return;
                        await fetch(this.disconnectUrlTemplate.replace('__ROUTER__', routerId), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ id: session.id }),
                        });
                        this.poll();
                    },

                    async block(phone, routerId) {
                        const session = this.live[phone];
                        if (!session || !session.address) return;
                        if (!confirm(`Block ${phone} (${session.address})? They will be denied access until unblocked in Winbox.`)) return;
                        await fetch(this.blockUrlTemplate.replace('__ROUTER__', routerId), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ address: session.address }),
                        });
                        this.poll();
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

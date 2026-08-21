{{-- Expects $users (paginated PppoeUser|HotspotUser), $plans (current tab's Plan list, for
     the filter bar), $allPlans (all plans keyed by id, for row display), $pppoePlans/
     $hotspotPlans (for the Add Customer modal's Package dropdown), $routers (keyed by id),
     $tab ('pppoe'|'hotspot'), $stats, $pppoeCount, $hotspotCount in scope. --}}
<x-sidebar-layout title="Customers">
    {{-- === STAT TILES === --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
        @php
            $oneispCustomerStatTiles = [
                ['label' => 'Total Users', 'value' => $stats['total'], 'icon' => 'bx-user', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'text' => 'text-indigo-600 dark:text-indigo-400'],
                ['label' => 'Active Users', 'value' => $stats['active'], 'icon' => 'bx-user-check', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                ['label' => 'Expired Users', 'value' => $stats['expired'], 'icon' => 'bx-user-x', 'bg' => 'bg-rose-50 dark:bg-rose-900/20', 'text' => 'text-rose-600 dark:text-rose-400'],
                ['label' => 'Disabled Users', 'value' => $stats['disabled'], 'icon' => 'bx-user-minus', 'bg' => 'bg-gray-100 dark:bg-gray-800', 'text' => 'text-gray-500 dark:text-gray-400'],
            ];
        @endphp
        @foreach($oneispCustomerStatTiles as $tile)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-2xl font-fira font-extrabold text-gray-900 dark:text-white">{{ number_format($tile['value']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $tile['label'] }}</p>
                </div>
                <div class="w-11 h-11 rounded-full {{ $tile['bg'] }} flex items-center justify-center shrink-0">
                    <i class="bx {{ $tile['icon'] }} text-xl {{ $tile['text'] }}"></i>
                </div>
            </div>
        @endforeach
    </div>

    {{-- === FILTERS === --}}
    <form method="GET" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-5 mb-3">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Filters</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Site</label>
                <select name="router_id" onchange="this.form.submit()" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                    <option value="">All</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" @selected(request('router_id') == $router->id)>{{ $router->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                    <option value="">All</option>
                    @foreach(($tab === 'hotspot' ? ['active', 'expired', 'offline', 'unused'] : ['active', 'expired', 'offline']) as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Connection</label>
                <select name="tab" onchange="this.form.submit()" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                    <option value="pppoe" @selected($tab === 'pppoe')>PPPoE</option>
                    <option value="hotspot" @selected($tab === 'hotspot')>Hotspot</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Package</label>
                <select name="plan_id" onchange="this.form.submit()" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                    <option value="">All</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Search</label>
            <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500">
                <button type="submit" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors shrink-0" title="Search">
                    <i class="bx bx-search text-lg"></i>
                </button>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by username, name, or phone…" class="bg-transparent border-none outline-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
                @if(request('search'))
                    <a href="{{ route('customers.index', array_filter(array_merge(request()->except(['search', 'page']), ['tab' => $tab]))) }}" class="text-gray-400 hover:text-rose-500 transition-colors shrink-0" title="Clear search">
                        <i class="bx bx-x text-lg"></i>
                    </a>
                @endif
            </div>
        </div>
        @if(request()->hasAny(['search', 'status', 'plan_id', 'router_id']))
            <div class="mt-3">
                <a href="{{ route('customers.index', ['tab' => $tab]) }}" class="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">Clear filters</a>
            </div>
        @endif
    </form>

    {{-- === TABS + ACTIONS === --}}
    <div x-data="{ showAddModal: {{ $errors->any() || request('add') ? 'true' : 'false' }}, connectionType: '{{ old('_connection_type', $tab) }}' }" class="mb-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('customers.index', array_filter(['tab' => 'pppoe', 'status' => request('status'), 'search' => request('search')])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-bold transition-colors {{ $tab === 'pppoe' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    PPPoE ({{ $pppoeCount }})
                </a>
                <a href="{{ route('customers.index', array_filter(['tab' => 'hotspot', 'status' => request('status'), 'search' => request('search')])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-bold transition-colors {{ $tab === 'hotspot' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    Hotspot ({{ $hotspotCount }})
                </a>
            </div>

            <div class="flex items-center gap-2">
                <x-per-page-select />
                <button type="button" @click="showAddModal = true" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    <i class="bx bx-user-plus text-lg"></i> New Customer
                </button>
            </div>
        </div>

        {{-- === ADD CUSTOMER MODAL === --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="showAddModal = false" x-show="showAddModal" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="showAddModal"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-2xl bg-white dark:bg-gray-900 shadow-xl h-full flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Customer</h3>
                    <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <form method="POST" class="flex flex-col flex-1 min-h-0"
                      :action="connectionType === 'hotspot' ? '{{ route('hotspot-users.store') }}' : '{{ route('pppoe-users.store') }}'">
                    @csrf
                    <input type="hidden" name="_connection_type" :value="connectionType">
                    <input type="hidden" name="redirect_to" :value="`{{ route('customers.index') }}?tab=${connectionType}`">

                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">First Name</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="John" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Last Name</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone Number <span class="text-rose-500">*</span></label>
                            <input type="tel" name="phone_number" required value="{{ old('phone_number') }}" placeholder="0712345678" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            @error('phone_number') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Street, town" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Connection Type <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="connectionType = 'hotspot'"
                                        class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border transition-colors"
                                        :class="connectionType === 'hotspot' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400'">
                                    <i class="bx bx-broadcast text-base"></i> Hotspot
                                </button>
                                <button type="button" @click="connectionType = 'pppoe'"
                                        class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border transition-colors"
                                        :class="connectionType === 'pppoe' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400'">
                                    <i class="bx bx-desktop text-base"></i> PPPoE
                                </button>
                            </div>
                        </div>

                        <template x-if="connectionType === 'pppoe'">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username <span class="text-rose-500">*</span></label>
                                <input type="text" name="username" :required="connectionType === 'pppoe'" value="{{ old('username') }}" placeholder="e.g., johndoe" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                @error('username') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </template>
                        <template x-if="connectionType === 'pppoe'">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Password <span class="text-rose-500">*</span></label>
                                <input type="text" name="password" :required="connectionType === 'pppoe'" value="{{ old('password') }}" placeholder="RADIUS login password" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                @error('password') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </template>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package</label>
                            <select name="current_plan_id" x-show="connectionType === 'hotspot'" :disabled="connectionType !== 'hotspot'" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors cursor-pointer">
                                <option value="">— None —</option>
                                @foreach($hotspotPlans as $plan)
                                    <option value="{{ $plan->id }}" @selected(old('current_plan_id') == $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                            <select name="current_plan_id" x-show="connectionType === 'pppoe'" :disabled="connectionType !== 'pppoe'" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors cursor-pointer">
                                <option value="">— None —</option>
                                @foreach($pppoePlans as $plan)
                                    <option value="{{ $plan->id }}" @selected(old('current_plan_id') == $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 rounded-lg shadow-sm transition-colors inline-flex items-center justify-center gap-2">
                            <i class="bx bx-save text-lg"></i> Save Customer
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    {{-- === TABLE === --}}
    <div x-data="customersLiveStatus('{{ $tab }}')" x-init="startPolling()" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-[11px] text-gray-400 uppercase tracking-wider font-bold">
                        <th class="px-6 py-3">Username</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Phone Number</th>
                        <th class="px-6 py-3">Plan</th>
                        <th class="px-6 py-3">Online</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Expiry</th>
                        <th class="px-6 py-3">Registered</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($users as $user)
                        @php
                            $oneispKey = $tab === 'hotspot' ? $user->phone_number : $user->username;
                            $oneispDetailUrl = route('customers.show', ['token' => \App\Http\Controllers\CustomerController::tokenFor($tab, $user->id)]);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/60 transition-colors">
                            <td class="px-6 py-4 font-fira">
                                <a href="{{ $oneispDetailUrl }}" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                    {{ $tab === 'hotspot' ? $user->phone_number : $user->username }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->name ?: '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->phone_number ?: '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $allPlans[$user->current_plan_id]->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <template x-if="live['{{ $oneispKey }}']?.online">
                                    <x-status-badge color="green" dot pulse>Online</x-status-badge>
                                </template>
                                <template x-if="live['{{ $oneispKey }}'] && !live['{{ $oneispKey }}'].online">
                                    <x-status-badge color="gray">Offline</x-status-badge>
                                </template>
                                <template x-if="!live['{{ $oneispKey }}']">
                                    <span class="text-[10px] text-gray-300 dark:text-gray-700">·</span>
                                </template>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->status === 'active')
                                    <x-status-badge color="green" dot>Active</x-status-badge>
                                @elseif($user->status === 'expired')
                                    <x-status-badge color="amber">Expired</x-status-badge>
                                @elseif($user->status === 'unused')
                                    <x-status-badge color="gray">Unused</x-status-badge>
                                @else
                                    <x-status-badge color="red">Offline</x-status-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->expires_at?->format('H:i M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $user->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <i class="bx bx-user text-4xl mb-3 text-gray-200 dark:text-gray-800"></i>
                                <p class="text-xs tracking-widest uppercase">No {{ $tab === 'hotspot' ? 'hotspot' : 'PPPoE' }} customers found.</p>
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
            function customersLiveStatus(tab) {
                return {
                    live: {},
                    tab,
                    keys: @json($tab === 'hotspot' ? $users->pluck('phone_number') : $users->pluck('username')),

                    startPolling() {
                        if (this.keys.length === 0) return;
                        this.poll();
                        setInterval(() => this.poll(), 5000);
                    },

                    async poll() {
                        try {
                            const params = new URLSearchParams();
                            const paramName = this.tab === 'hotspot' ? 'phone_numbers[]' : 'usernames[]';
                            this.keys.forEach(k => params.append(paramName, k));
                            const url = this.tab === 'hotspot'
                                ? `{{ route('hotspot-users.live-status') }}?${params}`
                                : `{{ route('pppoe-users.live-status') }}?${params}`;
                            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            const json = await res.json();
                            this.live = json.data || {};
                        } catch (e) {
                            // Leave `live` as-is on a transient failure — badges just stop updating.
                        }
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

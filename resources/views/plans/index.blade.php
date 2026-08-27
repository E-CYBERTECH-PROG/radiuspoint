{{-- Expects $plans (paginated, current $tab's Plan list), $activeRouterCount, $syncCounts,
     $tab ('pppoe'|'hotspot'), $pppoeCount, $hotspotCount, $routers, $planRouterIds (plan id =>
     array of router ids, for the Edit modal) in scope. --}}
<x-sidebar-layout title="Packages">
    @php
        $oneispEditingPlanId = old('_editing_plan_id', request('edit'));
    @endphp
    <div x-data="{ showAddModal: {{ $errors->any() && !$oneispEditingPlanId ? 'true' : 'false' }}, editingPlanId: {{ $oneispEditingPlanId ? (int) $oneispEditingPlanId : 'null' }} }">

        {{-- === TABS === --}}
        <div class="flex items-center gap-2 mb-3">
            <a href="{{ route('plans.index', array_filter(['tab' => 'pppoe', 'search' => request('search')])) }}"
               class="px-4 py-2 rounded-lg text-sm font-bold transition-colors {{ $tab === 'pppoe' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-transparent text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                PPPoE ({{ $pppoeCount }})
            </a>
            <a href="{{ route('plans.index', array_filter(['tab' => 'hotspot', 'search' => request('search')])) }}"
               class="px-4 py-2 rounded-lg text-sm font-bold transition-colors {{ $tab === 'hotspot' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-transparent text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                Hotspot ({{ $hotspotCount }})
            </a>
        </div>

        {{-- === ADD PACKAGE MODAL === --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="showAddModal = false" x-show="showAddModal" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="showAddModal"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-2xl bg-white dark:bg-gray-950 shadow-xl h-full flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Package</h3>
                    <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <form action="{{ route('plans.store') }}" method="POST" class="flex flex-col flex-1 min-h-0">
                    @csrf

                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g., 10Mbps Premium Home" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package Type <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border cursor-pointer transition-colors has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                    <input type="radio" name="type" value="hotspot" class="hidden" @checked(old('type', $tab) === 'hotspot')>
                                    <i class="bx bx-broadcast text-base"></i> Hotspot
                                </label>
                                <label class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border cursor-pointer transition-colors has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                    <input type="radio" name="type" value="pppoe" class="hidden" @checked(old('type', $tab) === 'pppoe')>
                                    <i class="bx bx-desktop text-base"></i> PPPoE (Fixed)
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Upload Speed <span class="text-red-500">*</span></label>
                            <input type="text" name="upload_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ old('upload_speed') }}" placeholder="5M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                            @error('upload_speed') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Download Speed <span class="text-red-500">*</span></label>
                            <input type="text" name="download_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ old('download_speed') }}" placeholder="5M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                            @error('download_speed') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Period <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <input type="number" name="duration_value" required min="1" value="{{ old('duration_value') }}" placeholder="30" class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                <select name="duration_unit" required class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                                    @foreach(\App\Models\Plan::DURATION_UNITS as $unit)
                                        <option value="{{ $unit }}" @selected(old('duration_unit', 'days') === $unit)>{{ ucfirst($unit) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Price (KES) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 text-sm">KES</span>
                                <input type="number" name="price" required min="0" step="0.01" value="{{ old('price') }}" placeholder="2500" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 pl-12 pr-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                            </div>
                            @error('price') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-3 rounded-lg shadow-sm transition-colors inline-flex items-center justify-center gap-2">
                            <i class='bx bx-cloud-upload text-lg'></i> Save &amp; Sync to Hardware
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        {{-- === TABLE CARD === --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <form method="GET" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span>Show</span>
                    <select name="per_page" onchange="this.form.submit()" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-2 py-1.5 text-gray-700 dark:text-gray-300 outline-none">
                        @foreach([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>Entries</span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <label for="plan-search">Search:</label>
                        <input id="plan-search" type="text" name="search" value="{{ request('search') }}" onchange="this.form.submit()" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-1.5 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="button" @click="showAddModal = true" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2 px-4 rounded-lg shadow-sm transition-colors">
                        New Package
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-[11px] text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Price</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Created On</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($plans as $i => $plan)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/60 transition-colors">
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $plans->firstItem() + $i }}</td>
                                <td class="px-6 py-3 text-gray-900 dark:text-white font-bold">{{ $plan->name }}</td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300">KES {{ number_format($plan->price) }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                        <i class="bx {{ $plan->type === 'hotspot' ? 'bx-broadcast' : 'bx-desktop' }}"></i>
                                        {{ $plan->type === 'hotspot' ? 'Hotspot' : 'Fixed' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    @if($plan->status === 'active')
                                        <x-status-badge color="green">Active</x-status-badge>
                                    @else
                                        <x-status-badge color="gray">Inactive</x-status-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $plan->created_at->format('H:i M d, Y') }}</td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" @click="editingPlanId = {{ $plan->id }}" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Edit"><i class="bx bx-edit-alt text-lg"></i></button>
                                        <form action="{{ route('plans.duplicate', $plan) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Duplicate"><i class="bx bx-copy text-lg"></i></button>
                                        </form>
                                        <a href="{{ route('plans.sync-status', $plan) }}" class="text-emerald-500 hover:text-emerald-600 transition-colors" title="View Sync Status"><i class="bx bx-show text-lg"></i></a>
                                        <form action="{{ route('plans.destroy', $plan) }}" method="POST" onsubmit="return rpConfirm(event, 'Delete this plan? Customers assigned to it must be reassigned first.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-600 transition-colors" title="Delete"><i class="bx bx-trash text-lg"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <i class='bx bx-box text-4xl mb-3 text-gray-200 dark:text-gray-800'></i>
                                    <p class="text-xs tracking-widest uppercase">No {{ $tab === 'hotspot' ? 'hotspot' : 'fixed' }} packages yet — add your first one to get started.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4">{{ $plans->links() }}</div>
        </div>

        {{-- === EDIT PACKAGE MODALS — one per row, toggled by editingPlanId === --}}
        @foreach($plans as $plan)
            @php
                [$oneispUpload, $oneispDownload] = array_pad(explode('/', $plan->speed_limit ?? ''), 2, '');
                $oneispSelectedRouterIds = old('_editing_plan_id') == $plan->id
                    ? old('router_ids', [])
                    : ($planRouterIds[$plan->id] ?? []);
            @endphp
            <div x-show="editingPlanId === {{ $plan->id }}" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
                <div class="absolute inset-0 bg-gray-900/60" @click="editingPlanId = null" x-show="editingPlanId === {{ $plan->id }}" x-transition.opacity></div>
                <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="editingPlanId === {{ $plan->id }}"
                     x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                    <div class="w-screen max-w-2xl bg-white dark:bg-gray-950 shadow-xl h-full flex flex-col">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Package</h3>
                            <button type="button" @click="editingPlanId = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <i class="bx bx-x text-2xl"></i>
                            </button>
                        </div>

                        <form action="{{ route('plans.update', $plan) }}" method="POST" class="flex flex-col flex-1 min-h-0">
                            @csrf @method('PUT')
                            <input type="hidden" name="_editing_plan_id" value="{{ $plan->id }}">

                            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" required value="{{ old('_editing_plan_id') == $plan->id ? old('name') : $plan->name }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                    @if(old('_editing_plan_id') == $plan->id) @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror @endif
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package Type <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border cursor-pointer transition-colors has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                            <input type="radio" name="type" value="hotspot" class="hidden" @checked((old('_editing_plan_id') == $plan->id ? old('type') : $plan->type) === 'hotspot')>
                                            <i class="bx bx-broadcast text-base"></i> Hotspot
                                        </label>
                                        <label class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border cursor-pointer transition-colors has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                            <input type="radio" name="type" value="pppoe" class="hidden" @checked((old('_editing_plan_id') == $plan->id ? old('type') : $plan->type) === 'pppoe')>
                                            <i class="bx bx-desktop text-base"></i> PPPoE (Fixed)
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Status <span class="text-red-500">*</span></label>
                                    <select name="status" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                                        <option value="active" @selected((old('_editing_plan_id') == $plan->id ? old('status') : $plan->status) === 'active')>Active</option>
                                        <option value="inactive" @selected((old('_editing_plan_id') == $plan->id ? old('status') : $plan->status) === 'inactive')>Inactive</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Price (KES) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 text-sm">KES</span>
                                        <input type="number" name="price" required min="0" step="0.01" value="{{ old('_editing_plan_id') == $plan->id ? old('price') : $plan->price }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 pl-12 pr-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                    </div>
                                    @if(old('_editing_plan_id') == $plan->id) @error('price') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Upload Speed <span class="text-red-500">*</span></label>
                                    <input type="text" name="upload_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ old('_editing_plan_id') == $plan->id ? old('upload_speed') : $oneispUpload }}" placeholder="5M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                    @if(old('_editing_plan_id') == $plan->id) @error('upload_speed') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror @endif
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Download Speed <span class="text-red-500">*</span></label>
                                    <input type="text" name="download_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ old('_editing_plan_id') == $plan->id ? old('download_speed') : $oneispDownload }}" placeholder="5M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                    @if(old('_editing_plan_id') == $plan->id) @error('download_speed') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Period <span class="text-red-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="number" name="duration_value" required min="1" value="{{ old('_editing_plan_id') == $plan->id ? old('duration_value') : $plan->duration_value }}" class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                        <select name="duration_unit" required class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                                            @foreach(\App\Models\Plan::DURATION_UNITS as $unit)
                                                <option value="{{ $unit }}" @selected((old('_editing_plan_id') == $plan->id ? old('duration_unit') : $plan->duration_unit) === $unit)>{{ ucfirst($unit) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Data Cap (MB) <span class="text-gray-400 normal-case">(optional)</span></label>
                                    <input type="number" name="data_cap_mb" min="1" value="{{ old('_editing_plan_id') == $plan->id ? old('data_cap_mb') : $plan->data_cap_mb }}" placeholder="Unlimited if blank" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Fair Use Speed <span class="text-gray-400 normal-case">(optional — throttled speed once the data cap is hit)</span></label>
                                    <input type="text" name="fup_speed_limit" pattern="\d+[kKmM]/\d+[kKmM]" title="Number + K or M, a slash, then number + K or M — e.g. 1M/1M." value="{{ old('_editing_plan_id') == $plan->id ? old('fup_speed_limit') : $plan->fup_speed_limit }}" placeholder="1M/1M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Portal Caption <span class="text-gray-400 normal-case">(optional)</span></label>
                                    <input type="text" name="caption" maxlength="255" value="{{ old('_editing_plan_id') == $plan->id ? old('caption') : $plan->caption }}" placeholder="e.g. Best for streaming Netflix" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Applies To <span class="text-gray-400 normal-case">(optional — leave all unchecked for every router)</span></label>
                                    @if($routers->isEmpty())
                                        <p class="text-sm text-gray-400">No routers deployed yet — this plan will sync to any router added later.</p>
                                    @else
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($routers as $router)
                                                <label class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 cursor-pointer">
                                                    <input type="checkbox" name="router_ids[]" value="{{ $router->id }}" @checked(in_array($router->id, $oneispSelectedRouterIds)) class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $router->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            </div>

                            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-3 rounded-lg shadow-sm transition-colors inline-flex items-center justify-center gap-2">
                                    <i class='bx bx-cloud-upload text-lg'></i> Save &amp; Re-Sync to Hardware
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-sidebar-layout>

{{-- Expects $plans (hotspot plans, for the modal), $vouchers (paginated HotspotUser list,
     current $tab), $tab ('available'|'online'|'expired'), $counts (status => count),
     $plansById (all plans keyed by id, for the Package column) in scope. --}}
<x-sidebar-layout title="Vouchers">

    <div x-data="{ showAddModal: {{ $errors->any() || request('add') ? 'true' : 'false' }}, mode: 'single' }">

        {{-- === TABS === --}}
        <div class="flex items-center gap-2 mb-3">
            @foreach(['available' => 'Available', 'online' => 'Online', 'expired' => 'Expired'] as $key => $label)
                <a href="{{ route('vouchers.index', array_filter(['tab' => $key, 'search' => request('search')])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-bold transition-colors {{ $tab === $key ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-transparent text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    {{ $label }} ({{ $counts[\App\Http\Controllers\VoucherController::STATUS_MAP[$key]] ?? 0 }})
                </a>
            @endforeach
        </div>

        {{-- === NEW VOUCHER MODAL === --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="showAddModal = false" x-show="showAddModal" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="showAddModal"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-lg bg-white dark:bg-gray-950 shadow-xl h-full flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">New Voucher</h3>
                    <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <form action="{{ route('vouchers.generate') }}" method="POST" class="flex flex-col flex-1 min-h-0">
                    @csrf
                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package <span class="text-red-500">*</span></label>
                        <select name="plan_id" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors cursor-pointer">
                            @forelse($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — KES {{ number_format($plan->price) }}</option>
                            @empty
                                <option value="" disabled>No hotspot packages yet — create one first</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Mode <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border cursor-pointer transition-colors" :class="mode === 'single' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'">
                                <input type="radio" name="mode" value="single" x-model="mode" class="hidden">
                                Single
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold border cursor-pointer transition-colors" :class="mode === 'batch' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'">
                                <input type="radio" name="mode" value="batch" x-model="mode" class="hidden">
                                Batch
                            </label>
                        </div>
                    </div>

                    <div x-show="mode === 'single'">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Voucher Code <span class="text-gray-400 normal-case">(optional — auto-generated if blank)</span></label>
                        <input type="text" name="username" placeholder="Leave blank to auto-generate" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    </div>

                    <div x-show="mode === 'single'">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Send To Phone <span class="text-gray-400 normal-case">(optional)</span></label>
                        <input type="tel" name="phone" placeholder="0712345678" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    </div>

                    <div x-show="mode === 'batch'">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Quantity <span class="text-red-500">*</span> <span class="text-gray-400 normal-case">(max 21 per print page)</span></label>
                        <input type="number" name="quantity" min="1" max="21" value="21" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    </div>

                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 rounded-lg shadow-sm transition-colors inline-flex items-center justify-center gap-2">
                            <i class="bx bx-barcode-reader text-lg"></i> Generate
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
                        <label for="voucher-search">Search:</label>
                        <input id="voucher-search" type="text" name="search" value="{{ request('search') }}" onchange="this.form.submit()" placeholder="Voucher code…" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-1.5 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="button" @click="showAddModal = true" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2 px-4 rounded-lg shadow-sm transition-colors">
                        New Voucher
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-[11px] text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-3">Code</th>
                            <th class="px-6 py-3">Package</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Expiry</th>
                            <th class="px-6 py-3">Created On</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($vouchers as $voucher)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/60 transition-colors">
                                <td class="px-6 py-3 font-fira font-bold text-gray-900 dark:text-white">{{ $voucher->phone_number }}</td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $plansById[$voucher->current_plan_id]->name ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    @if($voucher->status === 'active')
                                        <x-status-badge color="green">online</x-status-badge>
                                    @elseif($voucher->status === 'expired')
                                        <x-status-badge color="red">expired</x-status-badge>
                                    @else
                                        <x-status-badge color="gray">available</x-status-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $voucher->expires_at?->format('H:i M d, Y') ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $voucher->created_at->format('H:i M d, Y') }}</td>
                                <td class="px-6 py-3 text-right">
                                    <form action="{{ route('vouchers.destroy', $voucher) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this voucher? This also removes its RADIUS credential.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-600 transition-colors" title="Delete"><i class="bx bx-trash text-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-barcode-reader text-4xl mb-3 text-gray-200 dark:text-gray-800"></i>
                                    <p class="text-xs tracking-widest uppercase">No {{ $tab }} vouchers.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4">{{ $vouchers->links() }}</div>
        </div>
    </div>
</x-sidebar-layout>

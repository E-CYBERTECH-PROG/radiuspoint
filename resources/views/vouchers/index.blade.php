<x-sidebar-layout title="Vouchers">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Generate Vouchers</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create single or batch hotspot vouchers ready to print or send by SMS.</p>
    </div>

    <div x-data="{ mode: 'single' }" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 max-w-2xl">
        <form action="{{ route('vouchers.generate') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package <span class="text-red-500">*</span></label>
                <select name="plan_id" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    @forelse($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} — KES {{ number_format($plan->price) }}</option>
                    @empty
                        <option value="" disabled>No hotspot packages yet — create one first</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Mode <span class="text-red-500">*</span></label>
                <div class="flex gap-3">
                    <label class="flex-1 border rounded-lg p-3 flex items-center gap-2 cursor-pointer" :class="mode === 'single' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'">
                        <input type="radio" name="mode" value="single" x-model="mode" class="text-blue-600">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Single</span>
                    </label>
                    <label class="flex-1 border rounded-lg p-3 flex items-center gap-2 cursor-pointer" :class="mode === 'batch' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'">
                        <input type="radio" name="mode" value="batch" x-model="mode" class="text-blue-600">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Batch</span>
                    </label>
                </div>
            </div>

            <div x-show="mode === 'single'">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Voucher Code <span class="text-gray-400 normal-case">(optional — auto-generated if blank)</span></label>
                <input type="text" name="username" placeholder="Leave blank to auto-generate" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div x-show="mode === 'single'">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Send To Phone <span class="text-gray-400 normal-case">(optional)</span></label>
                <input type="tel" name="phone" placeholder="0712345678" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div x-show="mode === 'batch'">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Quantity <span class="text-red-500">*</span> <span class="text-gray-400 normal-case">(max 21 per print page)</span></label>
                <input type="number" name="quantity" min="1" max="21" value="21" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                    <i class="bx bx-barcode-reader text-lg"></i> Generate
                </button>
            </div>
        </form>
    </div>
</x-sidebar-layout>

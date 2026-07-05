<x-sidebar-layout title="Edit Plan">
    <div class="mb-6">
        <a href="{{ route('plans.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to Packages &amp; Plans
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Bandwidth Profile</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Changes will re-sync this profile to all active hardware.</p>
    </div>

    <form action="{{ route('plans.update', $plan) }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-4xl">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package Name / Identity</label>
                <input type="text" name="name" required value="{{ old('name', $plan->name) }}" placeholder="e.g., 10Mbps Premium Home" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Network Protocol</label>
                <select name="type" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="pppoe" @selected(old('type', $plan->type) === 'pppoe')>PPPoE (Home/Fiber)</option>
                    <option value="hotspot" @selected(old('type', $plan->type) === 'hotspot')>Hotspot (Public WiFi)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Retail Price (KES)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 text-sm">KES</span>
                    <input type="number" name="price" required value="{{ old('price', $plan->price) }}" placeholder="2500" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 pl-14 pr-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Validity</label>
                <div class="flex gap-2">
                    <input type="number" name="duration_value" required min="1" value="{{ old('duration_value', $plan->duration_value) }}" placeholder="30" class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                    <select name="duration_unit" required class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                        @foreach(['minutes', 'hours', 'days', 'weeks', 'months'] as $unit)
                            <option value="{{ $unit }}" @selected(old('duration_unit', $plan->duration_unit) === $unit)>{{ ucfirst($unit) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Bandwidth Cap (RX/TX)</label>
                <input type="text" name="speed_limit" required value="{{ old('speed_limit', $plan->speed_limit) }}" placeholder="5M/5M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-2">Format: Upload/Download (e.g., 5M/5M or 10M/20M)</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Data Cap (MB) <span class="text-gray-400 normal-case">(optional)</span></label>
                <input type="number" name="data_cap_mb" min="1" value="{{ old('data_cap_mb', $plan->data_cap_mb) }}" placeholder="Unlimited if blank" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-2">1 GB = 1000 MB</p>
            </div>

        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class='bx bx-cloud-upload text-lg'></i> Save &amp; Re-Sync to Hardware
            </button>
        </div>
    </form>
</x-sidebar-layout>

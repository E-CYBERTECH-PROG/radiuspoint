<x-sidebar-layout title="Add Plan">
    <div class="mb-6">
        <a href="{{ route('plans.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to Packages &amp; Plans
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Configure Bandwidth Profile</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Define internet packages. Profiles will automatically sync to all active hardware.</p>
    </div>

    <form action="{{ route('plans.store') }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-4xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package Name / Identity</label>
                <input type="text" name="name" required placeholder="e.g., 10Mbps Premium Home" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Network Protocol</label>
                <select name="type" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="pppoe">PPPoE (Home/Fiber)</option>
                    <option value="hotspot">Hotspot (Public WiFi)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Retail Price (KES)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 text-sm">KES</span>
                    <input type="number" name="price" required placeholder="2500" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 pl-14 pr-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Validity</label>
                <div class="flex gap-2">
                    <input type="number" name="duration_value" required min="1" placeholder="30" class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                    <select name="duration_unit" required class="w-1/2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                        @foreach(\App\Models\Plan::DURATION_UNITS as $unit)
                            <option value="{{ $unit }}" @selected(old('duration_unit', 'days') === $unit)>{{ ucfirst(rtrim($unit, 's')) }}(s)</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Bandwidth Cap (RX/TX)</label>
                <input type="text" name="speed_limit" required pattern="\d+[kKmM]/\d+[kKmM]" title="Number + K or M, a slash, then number + K or M — e.g. 5M/5M. No other separator is valid." placeholder="5M/5M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-2">Format: Upload/Download (e.g., 5M/5M or 10M/20M) — must use a slash, not a period or dash, or the router silently ignores it and the customer gets no cap at all.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Portal Caption <span class="text-gray-400 normal-case">(optional)</span></label>
                <input type="text" name="caption" maxlength="255" placeholder="e.g. Best for streaming Netflix" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-2">Shown on the captive portal instead of the auto-generated "Good for browsing…" line. Leave blank to keep the automatic one.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Data Cap (MB) <span class="text-gray-400 normal-case">(optional)</span></label>
                <input type="number" name="data_cap_mb" min="1" placeholder="Unlimited if blank" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-2">1 GB = 1000 MB</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Fair Use Speed <span class="text-gray-400 normal-case">(optional)</span></label>
                <input type="text" name="fup_speed_limit" pattern="\d+[kKmM]/\d+[kKmM]" title="Number + K or M, a slash, then number + K or M — e.g. 1M/1M." placeholder="1M/1M" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-2">Throttled speed once the data cap above is hit — ignored unless a data cap is also set.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Applies To <span class="text-gray-400 normal-case">(optional)</span></label>
                @if($routers->isEmpty())
                    <p class="text-sm text-gray-400">No routers deployed yet — this plan will sync to any router added later.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($routers as $router)
                            <label class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 cursor-pointer">
                                <input type="checkbox" name="router_ids[]" value="{{ $router->id }}" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $router->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Leave all unchecked to sync this plan to every router, including ones added later.</p>
                @endif
            </div>

        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class='bx bx-cloud-upload text-lg'></i> Save &amp; Sync to Hardware
            </button>
        </div>
    </form>
</x-sidebar-layout>

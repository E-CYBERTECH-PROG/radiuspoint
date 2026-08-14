<x-sidebar-layout title="Add OLT">
    <div class="mb-6">
        <a href="{{ route('olts.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to OLT Devices
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add OLT</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Connects over SSH — a raw remote terminal, not a structured API, since neither vendor exposes one.</p>
    </div>

    <form action="{{ route('olts.store') }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-2xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Estate-A OLT" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Brand <span class="text-red-500">*</span></label>
                <select name="brand" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="vsol" @selected(old('brand') === 'vsol')>VSOL</option>
                    <option value="hioso" @selected(old('brand') === 'hioso')>Hioso</option>
                    <option value="other" @selected(old('brand', 'other') === 'other')>Other</option>
                </select>
                @error('brand') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">IP Address <span class="text-red-500">*</span></label>
                <input type="text" name="ip_address" required value="{{ old('ip_address') }}" placeholder="10.0.0.50" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors font-fira">
                @error('ip_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">SSH Port</label>
                <input type="number" name="ssh_port" value="{{ old('ssh_port', 22) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors font-fira">
                @error('ssh_port') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" required value="{{ old('username', 'admin') }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors font-fira">
                @error('username') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors font-fira">
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">PON Ports</label>
                <input type="number" name="pon_ports" value="{{ old('pon_ports') }}" placeholder="e.g. 8" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                @error('pon_ports') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Notes</label>
                <textarea name="notes" rows="3" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class="bx bx-save text-lg"></i> Add OLT
            </button>
        </div>
    </form>
</x-sidebar-layout>

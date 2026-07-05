<x-sidebar-layout title="Edit Hotspot Customer">
    <div class="mb-6">
        <a href="{{ route('hotspot-users.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to Hotspot Users
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Hotspot Customer</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update this customer's details.</p>
    </div>

    <form action="{{ route('hotspot-users.update', $hotspot_user) }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-4xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" name="phone_number" required value="{{ old('phone_number', $hotspot_user->phone_number) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                @error('phone_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">MAC Address</label>
                <input type="text" name="mac_address" value="{{ old('mac_address', $hotspot_user->mac_address) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package</label>
                <select name="current_plan_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="">— None —</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($hotspot_user->current_plan_id == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Router</label>
                <select name="current_router_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="">— None —</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" @selected($hotspot_user->current_router_id == $router->id)>{{ $router->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="active" @selected($hotspot_user->status == 'active')>Active</option>
                    <option value="expired" @selected($hotspot_user->status == 'expired')>Expired</option>
                    <option value="offline" @selected($hotspot_user->status == 'offline')>Offline</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Expires At</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $hotspot_user->expires_at ? \Carbon\Carbon::parse($hotspot_user->expires_at)->format('Y-m-d\TH:i') : null) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class="bx bx-save text-lg"></i> Update Customer
            </button>
        </div>
    </form>
</x-sidebar-layout>

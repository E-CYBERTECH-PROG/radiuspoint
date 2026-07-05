<x-sidebar-layout title="Edit {{ $tenant->company_name }}">
    <div class="mb-6">
        <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to {{ $tenant->company_name }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Tenant</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update company details and subscription.</p>
    </div>

    <form action="{{ route('platform-admin.tenants.update', $tenant) }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-4xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Company Name <span class="text-red-500">*</span></label>
                <input type="text" name="company_name" required value="{{ old('company_name', $tenant->company_name) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                @error('company_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Subdomain</label>
                <input type="text" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                @error('subdomain') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Subscription Tier <span class="text-red-500">*</span></label>
                <select name="subscription_tier" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    @foreach(['free', 'starter', 'pro'] as $tier)
                        <option value="{{ $tier }}" @selected(old('subscription_tier', $tenant->subscription_tier) === $tier)>{{ ucfirst($tier) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Subscription Status <span class="text-red-500">*</span></label>
                <select name="subscription_status" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    @foreach(['trial', 'active', 'expired', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('subscription_status', $tenant->subscription_status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Subscription Expires At</label>
                <input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at', optional($tenant->subscription_expires_at)->format('Y-m-d')) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Admin Notes</label>
                <textarea name="admin_notes" rows="4" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">{{ old('admin_notes', $tenant->admin_notes) }}</textarea>
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class="bx bx-save text-lg"></i> Save Changes
            </button>
        </div>
    </form>
</x-sidebar-layout>

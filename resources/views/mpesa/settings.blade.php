<x-sidebar-layout title="M-Pesa Settings">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">M-Pesa Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daraja API credentials used to accept payments on your public payment portal.</p>
    </div>

    <form action="{{ route('mpesa-settings.update') }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Gateway Type <span class="text-red-500">*</span></label>
                <select name="gateway_type" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="till" @selected(old('gateway_type', $setting->gateway_type) === 'till')>Till (Buy Goods)</option>
                    <option value="paybill" @selected(old('gateway_type', $setting->gateway_type) === 'paybill')>Paybill</option>
                    <option value="bank" @selected(old('gateway_type', $setting->gateway_type) === 'bank')>Bank</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Environment <span class="text-red-500">*</span></label>
                <select name="environment" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="sandbox" @selected(old('environment', $setting->environment) === 'sandbox')>Sandbox (Testing)</option>
                    <option value="production" @selected(old('environment', $setting->environment) === 'production')>Production (Live)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Business ShortCode / Till Number <span class="text-red-500">*</span></label>
                <input type="text" name="shortcode" value="{{ old('shortcode', $setting->shortcode) }}" placeholder="174379" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div class="flex items-center gap-3 mt-8">
                <input type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $setting->is_active)) class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="is_active" class="text-sm font-bold text-gray-700 dark:text-gray-300">Gateway Active</label>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Consumer Key</label>
                <input type="password" name="consumer_key" placeholder="{{ $setting->exists && $setting->consumer_key ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Consumer Secret</label>
                <input type="password" name="consumer_secret" placeholder="{{ $setting->exists && $setting->consumer_secret ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Passkey</label>
                <input type="password" name="passkey" placeholder="{{ $setting->exists && $setting->passkey ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Settings</button>
        </div>
    </form>
</x-sidebar-layout>

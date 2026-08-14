{{-- $prefix: 'primary' or 'backup'; $setting: the MpesaSetting model for that slot --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Gateway Type @if($prefix === 'primary')<span class="text-red-500">*</span>@endif</label>
        <select name="{{ $prefix }}_gateway_type" {{ $prefix === 'primary' ? 'required' : '' }} class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
            <option value="till" @selected(old("{$prefix}_gateway_type", $setting->gateway_type) === 'till')>Till (Buy Goods)</option>
            <option value="paybill" @selected(old("{$prefix}_gateway_type", $setting->gateway_type) === 'paybill')>Paybill</option>
            <option value="bank" @selected(old("{$prefix}_gateway_type", $setting->gateway_type) === 'bank')>Bank</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Environment @if($prefix === 'primary')<span class="text-red-500">*</span>@endif</label>
        <select name="{{ $prefix }}_environment" {{ $prefix === 'primary' ? 'required' : '' }} class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
            <option value="sandbox" @selected(old("{$prefix}_environment", $setting->environment ?? 'sandbox') === 'sandbox')>Sandbox (Testing)</option>
            <option value="production" @selected(old("{$prefix}_environment", $setting->environment) === 'production')>Production (Live)</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Business ShortCode / Till Number @if($prefix === 'primary')<span class="text-red-500">*</span>@endif</label>
        <input type="text" name="{{ $prefix }}_shortcode" value="{{ old("{$prefix}_shortcode", $setting->shortcode) }}" placeholder="174379" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
    </div>

    <div class="flex items-center gap-3 mt-8">
        <input type="checkbox" name="{{ $prefix }}_is_active" value="1" id="{{ $prefix }}_is_active" @checked(old("{$prefix}_is_active", $setting->is_active)) class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <label for="{{ $prefix }}_is_active" class="text-sm font-bold text-gray-700 dark:text-gray-300">Gateway Active</label>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Consumer Key</label>
        <input type="password" name="{{ $prefix }}_consumer_key" placeholder="{{ $setting->exists && $setting->consumer_key ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
        <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Consumer Secret</label>
        <input type="password" name="{{ $prefix }}_consumer_secret" placeholder="{{ $setting->exists && $setting->consumer_secret ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
        <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Passkey</label>
        <input type="password" name="{{ $prefix }}_passkey" placeholder="{{ $setting->exists && $setting->passkey ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
        <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
    </div>
</div>

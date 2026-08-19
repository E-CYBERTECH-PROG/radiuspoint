<x-sidebar-layout title="Company Settings">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Company Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Shown to customers on every captive portal.</p>
    </div>

    @if(session('success'))
        <div class="max-w-3xl mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-400 text-sm font-bold px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('company-settings.update') }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-6 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Support Phone Number</label>
                <input type="text" name="support_phone" value="{{ old('support_phone', $tenant->support_phone) }}" placeholder="0712345678" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-1">Customers see this on the WiFi login page if they need help.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Location</label>
                <input type="text" name="location" value="{{ old('location', $tenant->location) }}" placeholder="e.g. Kilimani, Nairobi" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                <p class="text-xs text-gray-400 mt-1">Helps customers recognize your network by name.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Time Zone</label>
                <select name="timezone" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    @foreach($timezones as $value => $label)
                        <option value="{{ $value }}" @selected(old('timezone', $tenant->timezone) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Every date/time in the app (transactions, expiry, notifications) uses this.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Currency</label>
                <select name="currency_symbol" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency }}" @selected(old('currency_symbol', $tenant->currency_symbol) === $currency)>{{ $currency }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Shown next to every amount across the dashboard and captive portal.</p>
            </div>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg text-sm">Save</button>
    </form>
</x-sidebar-layout>

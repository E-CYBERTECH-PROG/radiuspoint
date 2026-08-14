<x-sidebar-layout title="M-Pesa Settings">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">M-Pesa Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daraja API credentials used to accept payments on your public payment portal.</p>
    </div>

    @if(session('success'))
        <div class="max-w-3xl mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-400 text-sm font-bold px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('mpesa-settings.update') }}" method="POST" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8">
            <div>
                <h2 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="bx bx-credit-card text-blue-500"></i> Primary Gateway</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Every payment is attempted here first.</p>
            </div>
            @include('mpesa._gateway_fields', ['prefix' => 'primary', 'setting' => $primary])
        </div>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8">
            <div>
                <h2 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="bx bx-shield-quarter text-amber-500"></i> Backup Gateway <span class="text-xs font-normal text-gray-400 normal-case">(optional)</span></h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used automatically if the primary gateway fails to start a payment — a customer never sees the failure, they just pay through this one instead.</p>
            </div>
            @include('mpesa._gateway_fields', ['prefix' => 'backup', 'setting' => $backup])
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Settings</button>
        </div>
    </form>
</x-sidebar-layout>

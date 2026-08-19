<x-sidebar-layout title="Payment Methods">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Payment Methods</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your M-Pesa gateways for accepting customer payments.</p>
    </div>

    @if(session('success'))
        <div class="max-w-3xl mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-400 text-sm font-bold px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div x-data="{ primaryEditing: {{ $primary->exists ? 'false' : 'true' }}, backupEditing: {{ $backup->exists ? 'false' : 'true' }} }" class="max-w-3xl">
        <form action="{{ route('mpesa-settings.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Primary: card once configured, otherwise straight into the form — there's
                 nothing to summarize yet. --}}
            <div x-show="!primaryEditing" x-cloak>
                <x-mpesa-gateway-card :setting="$primary" label="Primary" icon="bx-credit-card" color="blue" @click="primaryEditing = true" />
            </div>
            <div x-show="primaryEditing" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="bx bx-credit-card text-blue-500"></i> Primary Gateway</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Every payment is attempted here first.</p>
                    </div>
                    @if($primary->exists)
                        <button type="button" @click="primaryEditing = false" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Cancel</button>
                    @endif
                </div>
                @include('mpesa._gateway_fields', ['prefix' => 'primary', 'setting' => $primary])
            </div>

            {{-- Backup: same pattern as primary — card once configured, form otherwise. --}}
            <div x-show="!backupEditing" x-cloak>
                <x-mpesa-gateway-card :setting="$backup" label="Backup" icon="bx-shield-quarter" color="amber" @click="backupEditing = true" />
            </div>
            <div x-show="backupEditing" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="bx bx-shield-quarter text-amber-500"></i> Backup Gateway <span class="text-xs font-normal text-gray-400 normal-case">(optional)</span></h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Steps in automatically if the primary fails — customers never see the switch.</p>
                    </div>
                    @if($backup->exists)
                        <button type="button" @click="backupEditing = false" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Cancel</button>
                    @endif
                </div>
                @include('mpesa._gateway_fields', ['prefix' => 'backup', 'setting' => $backup])
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save</button>
            </div>
        </form>
    </div>
</x-sidebar-layout>

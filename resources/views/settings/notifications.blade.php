<x-sidebar-layout title="Notification Preferences">
    <div x-data="pushSubscriber()" x-init="checkStatus()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notification Preferences</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose which alerts reach you, and how.</p>
        </div>

        @if(session('status') === 'notification-preferences-updated')
            <div class="mb-6 px-4 py-3 rounded-lg text-sm font-bold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-900/50">
                Preferences saved.
            </div>
        @endif

        <div class="mb-6 px-4 py-3 rounded-lg text-sm bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50">
            SMS requires an SMS provider connected under <a href="{{ route('sms-settings.edit') }}" class="font-bold underline">SMS &rsaquo; Settings</a> and a phone number on your <a href="{{ route('profile.edit') }}" class="font-bold underline">profile</a>. Until both are set, SMS alerts are silently skipped rather than failing loudly.
        </div>

        <div class="mb-6 bg-white dark:bg-gray-950 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold text-gray-900 dark:text-white">Browser Push Notifications</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="statusText"></p>
            </div>
            <button type="button" @click="toggle()" x-show="supported" :disabled="busy"
                    class="inline-flex items-center gap-2 font-bold text-sm py-2 px-4 rounded-lg transition-colors disabled:opacity-50"
                    :class="subscribed ? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' : 'bg-blue-600 hover:bg-blue-700 text-white'">
                <i class="bx" :class="busy ? 'bx-loader-alt bx-spin' : 'bx-bell'"></i>
                <span x-text="subscribed ? 'Disable' : 'Enable'"></span>
            </button>
        </div>

        <form action="{{ route('settings.notifications.update') }}" method="POST">
            @csrf
            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-4">Alert</th>
                            <th class="px-6 py-4 text-center">Email</th>
                            <th class="px-6 py-4 text-center">In-App</th>
                            <th class="px-6 py-4 text-center">SMS</th>
                            <th class="px-6 py-4 text-center">Push</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @foreach($alertTypes as $type)
                            <tr>
                                <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">{{ $type->label() }}</td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="mail"
                                           @checked(in_array('mail', $preferences[$type->value]))
                                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="database"
                                           @checked(in_array('database', $preferences[$type->value]))
                                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="sms"
                                           @checked(in_array('sms', $preferences[$type->value]))
                                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="webpush"
                                           @checked(in_array('webpush', $preferences[$type->value]))
                                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="submit" class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg text-sm">Save Preferences</button>
        </form>
    </div>

    <x-slot name="scripts">
        <script>
            function pushSubscriber() {
                return {
                    supported: 'serviceWorker' in navigator && 'PushManager' in window,
                    subscribed: false,
                    busy: false,
                    statusText: 'Checking...',
                    vapidPublicKey: '{{ $vapidPublicKey }}',

                    async checkStatus() {
                        if (!this.supported) {
                            this.statusText = 'Not supported in this browser.';
                            return;
                        }
                        try {
                            const registration = await navigator.serviceWorker.register('/sw.js');
                            const existing = await registration.pushManager.getSubscription();
                            this.subscribed = !!existing;
                            this.statusText = this.subscribed
                                ? 'Enabled on this device.'
                                : 'Get a notification on this device the moment something needs attention.';
                        } catch (e) {
                            this.statusText = 'Could not check push status.';
                        }
                    },

                    urlBase64ToUint8Array(base64String) {
                        const padding = '='.repeat((4 - base64String.length % 4) % 4);
                        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                        const rawData = window.atob(base64);
                        return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
                    },

                    async toggle() {
                        this.busy = true;
                        try {
                            const registration = await navigator.serviceWorker.ready;
                            if (this.subscribed) {
                                const existing = await registration.pushManager.getSubscription();
                                if (existing) {
                                    await fetch('{{ route('push-subscriptions.destroy') }}', {
                                        method: 'DELETE',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                        body: JSON.stringify({ endpoint: existing.endpoint }),
                                    });
                                    await existing.unsubscribe();
                                }
                                this.subscribed = false;
                                this.statusText = 'Disabled on this device.';
                            } else {
                                const permission = await Notification.requestPermission();
                                if (permission !== 'granted') {
                                    this.statusText = 'Browser permission denied.';
                                    this.busy = false;
                                    return;
                                }
                                const subscription = await registration.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey),
                                });
                                await fetch('{{ route('push-subscriptions.store') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    body: JSON.stringify(subscription.toJSON()),
                                });
                                this.subscribed = true;
                                this.statusText = 'Enabled on this device.';
                            }
                        } catch (e) {
                            this.statusText = 'Something went wrong enabling push.';
                        }
                        this.busy = false;
                    },
                };
            }
        </script>
    </x-slot>
</x-sidebar-layout>

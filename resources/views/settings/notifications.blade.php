<x-sidebar-layout title="Notification Preferences">
    <div class="mb-4">
        <h1 class="mb-1">Notification Preferences</h1>
        <p class="text-muted mb-0">Choose which alerts reach you, and how.</p>
    </div>

    @if(session('status') === 'notification-preferences-updated')
        <div class="alert alert-success">Preferences saved.</div>
    @endif

    <div class="alert alert-warning">
        SMS requires an SMS provider connected under <a href="{{ route('sms-settings.edit') }}" class="fw-bold">SMS &rsaquo; Settings</a> and a phone number on your <a href="{{ route('profile.edit') }}" class="fw-bold">profile</a>. Until both are set, SMS alerts are silently skipped rather than failing loudly.
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center justify-content-between gap-3">
            <div>
                <p class="fw-bold mb-0">Browser Push Notifications</p>
                <p class="text-muted small mt-1 mb-0" id="rp-push-status">Checking...</p>
            </div>
            <button type="button" id="rp-push-toggle" class="btn" style="display:none">
                <i class="ti ti-bell" id="rp-push-icon"></i>
                <span id="rp-push-label">Enable</span>
            </button>
        </div>
    </div>

    <form action="{{ route('settings.notifications.update') }}" method="POST">
        @csrf
        <div class="card">
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Alert</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">In-App</th>
                            <th class="text-center">SMS</th>
                            <th class="text-center">Push</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alertTypes as $type)
                            <tr>
                                <td class="fw-bold">{{ $type->label() }}</td>
                                <td class="text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="mail"
                                           @checked(in_array('mail', $preferences[$type->value]))
                                           class="form-check-input">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="database"
                                           @checked(in_array('database', $preferences[$type->value]))
                                           class="form-check-input">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="sms"
                                           @checked(in_array('sms', $preferences[$type->value]))
                                           class="form-check-input">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="channels[{{ $type->value }}][]" value="webpush"
                                           @checked(in_array('webpush', $preferences[$type->value]))
                                           class="form-check-input">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Preferences</button>
    </form>

    <x-slot name="scripts">
        <script>
            (function () {
                var supported = 'serviceWorker' in navigator && 'PushManager' in window;
                var subscribed = false;
                var busy = false;
                var vapidPublicKey = '{{ $vapidPublicKey }}';
                var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                var statusEl = document.getElementById('rp-push-status');
                var toggleBtn = document.getElementById('rp-push-toggle');
                var iconEl = document.getElementById('rp-push-icon');
                var labelEl = document.getElementById('rp-push-label');

                function render() {
                    toggleBtn.disabled = busy;
                    iconEl.className = 'ti ' + (busy ? 'ti-loader-2 icon-spin' : 'ti-bell');
                    labelEl.textContent = subscribed ? 'Disable' : 'Enable';
                    toggleBtn.className = 'btn ' + (subscribed ? '' : 'btn-primary');
                }

                function urlBase64ToUint8Array(base64String) {
                    var padding = '='.repeat((4 - base64String.length % 4) % 4);
                    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    var rawData = window.atob(base64);
                    return Uint8Array.from([...rawData].map(function (c) { return c.charCodeAt(0); }));
                }

                async function checkStatus() {
                    if (!supported) {
                        statusEl.textContent = 'Not supported in this browser.';
                        return;
                    }
                    try {
                        var registration = await navigator.serviceWorker.register('/sw.js');
                        var existing = await registration.pushManager.getSubscription();
                        subscribed = !!existing;
                        statusEl.textContent = subscribed
                            ? 'Enabled on this device.'
                            : 'Get a notification on this device the moment something needs attention.';
                        toggleBtn.style.display = '';
                        render();
                    } catch (e) {
                        statusEl.textContent = 'Could not check push status.';
                    }
                }

                async function toggle() {
                    busy = true;
                    render();
                    try {
                        var registration = await navigator.serviceWorker.ready;
                        if (subscribed) {
                            var existing = await registration.pushManager.getSubscription();
                            if (existing) {
                                await fetch('{{ route('push-subscriptions.destroy') }}', {
                                    method: 'DELETE',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                                    body: JSON.stringify({ endpoint: existing.endpoint }),
                                });
                                await existing.unsubscribe();
                            }
                            subscribed = false;
                            statusEl.textContent = 'Disabled on this device.';
                        } else {
                            var permission = await Notification.requestPermission();
                            if (permission !== 'granted') {
                                statusEl.textContent = 'Browser permission denied.';
                                busy = false;
                                render();
                                return;
                            }
                            var subscription = await registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                            });
                            await fetch('{{ route('push-subscriptions.store') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                                body: JSON.stringify(subscription.toJSON()),
                            });
                            subscribed = true;
                            statusEl.textContent = 'Enabled on this device.';
                        }
                    } catch (e) {
                        statusEl.textContent = 'Something went wrong enabling push.';
                    }
                    busy = false;
                    render();
                }

                toggleBtn.addEventListener('click', toggle);
                checkStatus();
            })();
        </script>
    </x-slot>
</x-sidebar-layout>

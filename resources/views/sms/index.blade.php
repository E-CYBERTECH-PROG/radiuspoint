<x-sidebar-layout title="SMS Outbox">
    <div x-data="{ open: false, tab: '{{ in_array(request('tab'), ['settings', 'templates', 'automation']) ? request('tab') : 'outbox' }}', message: '' }" x-init="open = @json($errors->any())">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">SMS</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Outbound messages and reusable templates.</p>
                <span class="inline-flex items-center gap-1 mt-2 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full">
                    <i class="bx bx-info-circle"></i> Log Mode — No Gateway Connected
                </span>
            </div>
            <button @click="open = true" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-plus text-lg"></i> Compose SMS
            </button>
        </div>

        <div class="mb-4 flex gap-2">
            <button @click="tab = 'outbox'" :class="tab === 'outbox' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors">Outbox</button>
            <button @click="tab = 'templates'" :class="tab === 'templates' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors">Templates</button>
            <button @click="tab = 'settings'" :class="tab === 'settings' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors">Settings</button>
            <button @click="tab = 'automation'" :class="tab === 'automation' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800'" class="text-sm font-bold px-4 py-2 rounded-lg transition-colors">Automation</button>
        </div>

        <div x-show="tab === 'outbox'">
            <form method="GET" class="mb-4 flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="tab" value="outbox">
                <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
                    <i class="bx bx-search text-gray-400 text-lg"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone number..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
                </div>
                <select name="status" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                    <option value="">All Statuses</option>
                    @foreach(['queued', 'sent', 'failed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <x-per-page-select />
                <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('sms.index', ['tab' => 'outbox']) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
                @endif
            </form>

            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-4">Phone</th>
                            <th class="px-6 py-4">Message</th>
                            <th class="px-6 py-4">Initiator</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($messages as $msg)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-4 font-fira text-gray-900 dark:text-white">{{ $msg->phone_number }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 max-w-md truncate">{{ $msg->message }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $msg->initiator ?: 'System' }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $msg->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <x-status-badge :color="$msg->status === 'sent' ? 'green' : ($msg->status === 'failed' ? 'red' : 'amber')">
                                        {{ $msg->status }}
                                    </x-status-badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('sms.destroy', $msg) }}" method="POST" onsubmit="return confirm('Remove this message from the log?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove"><i class="bx bx-trash text-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-message-square-dots text-4xl mb-3 text-gray-200"></i>
                                    <p class="text-xs tracking-widest uppercase">No messages sent yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
            <div class="mt-4">{{ $messages->links() }}</div>
        </div>

        <div x-show="tab === 'templates'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6" x-data="{ editing: null }">
                @forelse($templates as $template)
                    <div class="border border-gray-200 dark:border-gray-800 rounded-lg p-4">
                        <template x-if="editing !== {{ $template->id }}">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $template->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $template->body }}</p>
                                </div>
                                <div class="flex items-start gap-2 shrink-0">
                                    <button type="button" @click="editing = {{ $template->id }}" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="bx bx-edit-alt text-lg"></i></button>
                                    <form action="{{ route('sms-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors"><i class="bx bx-trash text-lg"></i></button>
                                    </form>
                                </div>
                            </div>
                        </template>
                        <template x-if="editing === {{ $template->id }}">
                            <form action="{{ route('sms-templates.update', $template) }}" method="POST" class="space-y-2">
                                @csrf @method('PUT')
                                <input type="text" name="name" required value="{{ $template->name }}" class="w-full text-sm font-bold bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-blue-500">
                                <textarea name="body" required rows="2" class="w-full text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-blue-500">{{ $template->body }}</textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Save</button>
                                    <button type="button" @click="editing = null" class="text-xs text-gray-500 dark:text-gray-400 px-3 py-1.5">Cancel</button>
                                </div>
                            </form>
                        </template>
                    </div>
                @empty
                    <div class="col-span-2 text-center text-gray-400 py-12">
                        <i class="bx bx-file-blank text-4xl mb-3 text-gray-200"></i>
                        <p class="text-xs tracking-widest uppercase">No templates yet.</p>
                    </div>
                @endforelse
            </div>
            <form action="{{ route('sms-templates.store') }}" method="POST" class="border-t border-gray-100 dark:border-gray-800 pt-6 flex flex-col md:flex-row gap-3">
                @csrf
                <input type="text" name="name" required placeholder="Template name" class="md:w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="body" required placeholder="Message body" class="flex-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-5 py-2.5 rounded-lg">Add Template</button>
            </form>
        </div>

        <div x-show="tab === 'settings'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 max-w-2xl">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Gateway configuration for when a real SMS provider is connected. Messages are currently log-only and are not sent to any provider.</p>
            <form action="{{ route('sms-settings.update') }}" method="POST" class="space-y-5">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Provider</label>
                    <input type="text" name="provider" value="{{ old('provider', $setting->provider) }}" placeholder="e.g., Africa's Talking" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Sender ID</label>
                        <input type="text" name="sender_id" value="{{ old('sender_id', $setting->sender_id) }}" placeholder="RADIUSPOINT" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">API Username</label>
                        <input type="text" name="username" value="{{ old('username', $setting->username) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">API Key</label>
                        <input type="password" name="api_key" placeholder="{{ $setting->exists && $setting->api_key ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                        <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">API Secret</label>
                        <input type="password" name="api_secret" placeholder="{{ $setting->exists && $setting->api_secret ? '•••• saved' : '' }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                        <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Settings</button>
                </div>
            </form>
        </div>

        <div x-show="tab === 'automation'" x-cloak class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Fire an SMS automatically on a customer lifecycle event, using one of your templates below. Placeholders available: <code class="font-fira text-xs">{name} {plan} {expires_at} {code} {password}</code>. Leave a trigger off to change nothing.</p>
            <form action="{{ route('sms-triggers.update') }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                @php
                    $triggerLabels = [
                        'pppoe_expiry_reminder_3d' => ['PPPoE — Expiry Reminder (3 days before)', 'Sent once, 3 days before a PPPoE account expires.'],
                        'pppoe_expired' => ['PPPoE — Account Expired', 'Sent the moment a PPPoE account is flipped to expired.'],
                        'pppoe_renewed' => ['PPPoE — Renewal Confirmed', 'Sent whenever a PPPoE account is extended/renewed.'],
                        'pppoe_welcome' => ['PPPoE — Welcome Message', 'Sent when a new active PPPoE customer is added.'],
                        'pppoe_payment_receipt' => ['PPPoE — Payment Receipt', 'Reserved for a future online PPPoE payment flow — not fired yet.'],
                        'hotspot_purchase_confirmed' => ['Hotspot — Purchase Confirmed', 'Sent after a successful M-Pesa hotspot purchase. Replaces the built-in login-details message when enabled.'],
                        'hotspot_voucher_created' => ['Hotspot — Voucher Created', 'Sent when a voucher is generated with a phone number attached. Replaces the built-in voucher-code message when enabled.'],
                    ];
                @endphp
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($triggerLabels as $key => [$label, $help])
                        @php $trigger = $triggers[$key] ?? null; @endphp
                        <div class="py-4 flex flex-col md:flex-row md:items-center gap-3">
                            <div class="md:w-72 shrink-0">
                                <label class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white cursor-pointer">
                                    <input type="checkbox" name="triggers[{{ $key }}][enabled]" value="1" @checked($trigger?->enabled) class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ $label }}
                                </label>
                                <p class="text-xs text-gray-400 mt-1 ml-6">{{ $help }}</p>
                            </div>
                            <select name="triggers[{{ $key }}][sms_template_id]" class="flex-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">— No template selected —</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @selected($trigger?->sms_template_id === $template->id)>{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">Save Automation</button>
                </div>
            </form>
        </div>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="open = false" x-show="open" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="open"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-md bg-white dark:bg-gray-950 shadow-xl h-full flex flex-col">
                    <form action="{{ route('sms.store') }}" method="POST" class="flex flex-col h-full">
                        @csrf
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Compose SMS</h2>
                            <button type="button" @click="open = false"><i class="bx bx-x text-2xl text-gray-400"></i></button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone_number" required value="{{ old('phone_number') }}" placeholder="0712345678" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                @error('phone_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            @if($templates->count())
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Use Template <span class="text-gray-400 normal-case">(optional)</span></label>
                                    <select name="sms_template_id" x-on:change="const t = {{ $templates->pluck('body', 'id')->toJson() }}; if ($event.target.value) message = t[$event.target.value]" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">— None —</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Message <span class="text-red-500">*</span></label>
                                <textarea name="message" required rows="5" x-model="message" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"></textarea>
                                @error('message') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-sm transition-colors">Queue Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>

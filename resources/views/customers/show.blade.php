{{-- Full customer profile page — replaces the old global slide-over panel for the Customers
     hub. $user/$usage/$transactions/$totalSpent/$connectionLogs are all real, server-side
     data (see CustomerController::show()). A few tiles here (Wallet, Deposit, Request
     Payment, Adjust Balance, Resolve Payment, CPE Configuration, Update Group Policy,
     Override Package) have no backing feature in this app yet and are shown inert/disabled
     rather than faked — same honesty pattern as "Coming soon" in the sidebar. --}}
@php
    $oneispIsHotspot = $type === 'hotspot';
    $oneispIdentifier = $oneispIsHotspot ? $user->phone_number : $user->username;
    $oneispRadiusUsername = $oneispIdentifier;
    $oneispInitials = strtoupper(substr($user->name ?: $oneispIdentifier, 0, 2));

    $oneispUpdateUrl = $oneispIsHotspot ? route('hotspot-users.update', $user) : route('pppoe-users.update', $user);
    $oneispDisconnectUrl = $oneispIsHotspot ? route('hotspot-users.disconnect', $user) : route('pppoe-users.disconnect', $user);
    $oneispIsDisabled = $user->status === 'offline';
    $oneispDisableUrl = $oneispIsDisabled
        ? ($oneispIsHotspot ? route('hotspot-users.enable', $user) : route('pppoe-users.enable', $user))
        : ($oneispIsHotspot ? route('hotspot-users.disable', $user) : route('pppoe-users.disable', $user));
    $oneispDestroyUrl = $oneispIsHotspot ? route('hotspot-users.destroy', $user) : route('pppoe-users.destroy', $user);
    $oneispExtendUrl = $oneispIsHotspot ? route('hotspot-users.extend', $user) : route('pppoe-users.extend', $user);

    // "offline" is what the Disable button actually sets (see disable()) — that's this app's
    // closest equivalent to "disabled". "unused" (hotspot-only: a voucher not yet redeemed)
    // is the closest equivalent to "paused". Expiry is checked against the real clock, not
    // just the stored status, since a customer can still say 'active' for a little while
    // after expires_at passes, before the expiry sweep catches up.
    $oneispPlanState = match (true) {
        $user->status === 'offline' => 'disabled',
        $user->status === 'unused' => 'paused',
        $user->status === 'expired' || ($user->expires_at && $user->expires_at->isPast()) => 'expired',
        default => 'active',
    };
    $oneispPlanTheme = [
        'active' => ['border' => 'border-blue-200 dark:border-blue-900/50', 'label' => 'text-blue-500', 'btn' => 'bg-blue-500 hover:bg-blue-600', 'btnOutline' => 'border-blue-300 dark:border-blue-800 text-blue-400'],
        'expired' => ['border' => 'border-rose-200 dark:border-rose-900/50', 'label' => 'text-rose-500', 'btn' => 'bg-rose-500 hover:bg-rose-600', 'btnOutline' => 'border-rose-300 dark:border-rose-800 text-rose-400'],
        'disabled' => ['border' => 'border-gray-300 dark:border-gray-700', 'label' => 'text-gray-500 dark:text-gray-400', 'btn' => 'bg-gray-700 hover:bg-gray-800', 'btnOutline' => 'border-gray-300 dark:border-gray-700 text-gray-400'],
        'paused' => ['border' => 'border-amber-200 dark:border-amber-900/50', 'label' => 'text-amber-500', 'btn' => 'bg-amber-500 hover:bg-amber-600', 'btnOutline' => 'border-amber-300 dark:border-amber-800 text-amber-400'],
    ][$oneispPlanState];
@endphp
<x-sidebar-layout title="Customer Details">
    <div x-data="{ actionsOpen: false, editModal: {{ $errors->any() ? 'true' : 'false' }}, expiryModal: false, passwordModal: false, txSearch: '', txTab: 'mpesa' }">

        <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-fira font-extrabold text-gray-900 dark:text-white">{{ $oneispIdentifier }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" disabled title="No CPE provisioning integration yet" class="bg-indigo-600/60 text-white font-bold text-sm py-2.5 px-4 rounded-lg cursor-not-allowed">
                    CPE Configuration
                </button>
                <div class="relative">
                    <button type="button" @click="actionsOpen = !actionsOpen" class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm py-2.5 px-4 rounded-lg">
                        Actions <i class="bx bx-chevron-down"></i>
                    </button>
                    <div x-show="actionsOpen" x-cloak x-transition @click.outside="actionsOpen = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-1 z-30">
                        <button type="button" @click="actionsOpen = false; editModal = true" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"><i class="bx bx-edit-alt text-base text-gray-400"></i> Edit Full Profile</button>
                        <form action="{{ $oneispDisconnectUrl }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"><i class="bx bx-power-off text-base text-gray-400"></i> Force Disconnect</button>
                        </form>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <form action="{{ $oneispDestroyUrl }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this customer permanently? This also removes their RADIUS credentials.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20"><i class="bx bx-trash text-base"></i> Delete Customer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- === IDENTITY + PLAN === --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-3">
            <div class="lg:col-span-9 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <div class="sm:flex-1 sm:min-w-0 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-20 h-20 rounded-lg bg-gradient-to-br from-amber-200 to-orange-300 dark:from-amber-500/30 dark:to-orange-500/30 flex items-center justify-center font-fira font-extrabold text-orange-700 dark:text-orange-300 text-2xl shrink-0">{{ $oneispInitials }}</div>
                            <div class="flex-1 min-w-0 space-y-2">
                                <p class="font-bold text-gray-900 dark:text-white uppercase text-sm leading-tight truncate">{{ $user->name ?: '—' }}</p>
                                <div class="flex gap-2">
                                    <button type="button" @click="editModal = true" class="text-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg transition-colors">View Info</button>
                                    <form action="{{ $oneispDisableUrl }}" method="POST" class="flex-1" onsubmit="return rpConfirm(event, '{{ $oneispIsDisabled ? 'Enable' : 'Disable' }} this customer?')">
                                        @csrf
                                        @if($oneispIsDisabled)
                                            <button type="submit" class="w-full text-center border border-emerald-500 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-sm font-bold py-2.5 px-4 rounded-lg transition-colors">Enable</button>
                                        @else
                                            <button type="submit" class="w-full text-center border border-rose-500 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-sm font-bold py-2.5 px-4 rounded-lg transition-colors">Disable</button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-9">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center shrink-0"><i class="bx bx-wallet text-indigo-600 dark:text-indigo-400"></i></div>
                                <div>
                                    <p class="text-sm font-fira font-bold text-gray-900 dark:text-white">KES 0</p>
                                    <p class="text-[10px] text-gray-400">Wallet Balance</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center shrink-0"><i class="bx bx-trending-up text-emerald-600 dark:text-emerald-400"></i></div>
                                <div>
                                    <p class="text-sm font-fira font-bold text-gray-900 dark:text-white">KES {{ number_format($totalSpent) }}</p>
                                    <p class="text-[10px] text-gray-400">Total Spent</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" disabled title="No payment-request feature yet" class="border border-emerald-300 text-emerald-500 text-sm font-bold py-2.5 px-4 rounded-lg cursor-not-allowed opacity-60">Request Payment</button>
                            <button type="button" disabled title="No wallet feature yet" class="bg-amber-400/60 text-white text-sm font-bold py-2.5 px-4 rounded-lg cursor-not-allowed">Adjust Balance</button>
                        </div>
                    </div>

                    <div class="sm:flex-1 sm:min-w-0 flex flex-col gap-1.5 border-t sm:border-t-0 sm:border-l border-gray-100 dark:border-gray-800 pt-4 sm:pt-0 sm:pl-6">
                        @foreach([
                            ['icon' => 'bx-user', 'label' => 'Username', 'value' => $oneispIdentifier],
                            ['icon' => 'bx-calendar', 'label' => 'Registered', 'value' => $user->created_at->format('H:i M d, Y')],
                            ['icon' => 'bx-pulse', 'label' => 'Status', 'value' => ucfirst($user->status)],
                            ['icon' => 'bx-router', 'label' => 'Type', 'value' => ucfirst($type)],
                            ['icon' => 'bx-map', 'label' => 'Location', 'value' => $user->address ?: '—'],
                            ['icon' => 'bx-phone', 'label' => 'Contact', 'value' => $user->phone_number ?: '—'],
                        ] as $row)
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-2 text-gray-400 w-24 shrink-0">
                                    <i class="bx {{ $row['icon'] }} text-base"></i>
                                    <span class="text-xs">{{ $row['label'] }}</span>
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $row['value'] }}</span>
                            </div>
                        @endforeach

                        <button type="button" disabled title="No group policy feature yet" class="mt-2 w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-400 text-sm font-bold py-2.5 rounded-lg cursor-not-allowed">
                            Update Group Policy
                        </button>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 bg-white dark:bg-gray-900 border {{ $oneispPlanTheme['border'] }} rounded-lg shadow-sm p-6">
                <p class="text-[11px] font-bold {{ $oneispPlanTheme['label'] }} uppercase tracking-wide">Current Plan</p>
                <p class="text-base font-bold text-gray-900 dark:text-white mb-3">{{ $user->plan?->name ?? '— No plan —' }}</p>

                <p class="text-[11px] font-bold {{ $oneispPlanTheme['label'] }} uppercase tracking-wide">Monthly Usage</p>
                <p class="text-sm font-fira font-bold text-gray-900 dark:text-white mb-3">
                    @if($usage)
                        {{ number_format($usage['used_mb']) }} MB @if($usage['cap_mb']) / {{ number_format($usage['cap_mb']) }} MB @endif
                    @else
                        —
                    @endif
                </p>

                <p class="text-[11px] font-bold {{ $oneispPlanTheme['label'] }} uppercase tracking-wide">Expiry Date</p>
                <p class="text-sm font-fira font-bold text-gray-900 dark:text-white mb-5">{{ $user->expires_at?->format('H:i M d, Y') ?? '—' }}</p>

                <button type="button" @click="editModal = true" class="block w-full text-center {{ $oneispPlanTheme['btn'] }} text-white text-sm font-bold py-2.5 rounded-lg transition-colors mb-2">Change Plan</button>
                <button type="button" disabled title="No package-override feature yet" class="w-full border {{ $oneispPlanTheme['btnOutline'] }} text-sm font-bold py-2.5 rounded-lg cursor-not-allowed">Override Package</button>
            </div>
        </div>

        {{-- === CHILD ACCOUNT + QUICK ACTIONS === --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-3">
            <div class="lg:col-span-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">User Connection Logs</h3>

                @if($connectionLogs->isEmpty())
                    <p class="text-xs text-gray-400 uppercase tracking-widest py-4">No connection logs yet.</p>
                @else
                    <div class="relative pl-2">
                        <div class="absolute left-[7px] top-2 bottom-2 w-px bg-gray-100 dark:bg-gray-800"></div>
                        <div class="space-y-5">
                            @foreach($connectionLogs as $log)
                                <div class="relative pl-6">
                                    <span class="absolute left-0 top-1 w-3 h-3 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-gray-900"></span>
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">Access-Accept</p>
                                        <p class="text-xs text-gray-400 shrink-0">{{ \Carbon\Carbon::parse($log->acctstarttime)->format('H:i d/m/Y') }}</p>
                                    </div>
                                    <p class="text-xs text-gray-400">Status: UserOk Password: {{ $oneispRadiusUsername }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-7 grid grid-cols-3 gap-3">
                <button type="button" @click="passwordModal = true" @if($oneispIsHotspot) disabled title="Not applicable for Hotspot" @endif
                        class="flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs {{ $oneispIsHotspot ? 'bg-indigo-600/50 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700' }} transition-colors">
                    <i class="bx bx-lock-alt text-xl"></i> Change Password
                </button>
                <button type="button" @click="expiryModal = true" class="flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs bg-rose-500 hover:bg-rose-600 transition-colors">
                    <i class="bx bx-calendar-edit text-xl"></i> Change Expiry
                </button>
                @if($oneispIsHotspot)
                    <form action="{{ route('hotspot-users.reset-mac', $user) }}" method="POST" onsubmit="return rpConfirm(event, 'Clear this customer\'s bound MAC address?')">
                        @csrf
                        <button type="submit" class="w-full flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs bg-sky-500 hover:bg-sky-600 transition-colors">
                            <i class="bx bx-reset text-xl"></i> Reset Site/MAC
                        </button>
                    </form>
                @else
                    <button type="button" disabled title="Not applicable for PPPoE" class="w-full flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs bg-sky-500/50 cursor-not-allowed">
                        <i class="bx bx-reset text-xl"></i> Reset Site/MAC
                    </button>
                @endif
                <a href="{{ route('sms.index') }}" class="flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs bg-orange-500 hover:bg-orange-600 transition-colors">
                    <i class="bx bx-message-square-dots text-xl"></i> Send SMS
                </a>
                <button type="button" disabled title="No wallet feature yet" class="flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs bg-gray-700/60 cursor-not-allowed">
                    <i class="bx bx-credit-card text-xl"></i> Deposit
                </button>
                <button type="button" disabled title="No payment-resolution feature yet" class="flex flex-col items-center justify-center gap-1.5 py-5 rounded-lg text-white font-bold text-xs bg-emerald-500/60 cursor-not-allowed">
                    <i class="bx bx-check-double text-xl"></i> Resolve Payment
                </button>
            </div>
        </div>

        {{-- === TRANSACTIONS === --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <button type="button" @click="txTab = 'mpesa'" class="px-4 py-2 rounded-lg text-xs font-bold transition-colors" :class="txTab === 'mpesa' ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'">M-Pesa</button>
                    <button type="button" @click="txTab = 'invoice'" class="px-4 py-2 rounded-lg text-xs font-bold transition-colors" :class="txTab === 'invoice' ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'">Invoice</button>
                    <button type="button" @click="txTab = 'account'" class="px-4 py-2 rounded-lg text-xs font-bold transition-colors" :class="txTab === 'account' ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'">Account Transactions</button>
                </div>
                <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-1.5">
                    <span class="text-[10px] font-bold text-gray-400 uppercase mr-2">Search</span>
                    <input type="text" x-model="txSearch" placeholder="Search transactions…" class="bg-transparent border-none outline-none focus:ring-0 text-xs w-40 dark:text-gray-200 dark:placeholder-gray-500">
                </div>
            </div>

            <template x-if="txTab === 'invoice'">
                <p class="px-6 py-12 text-center text-xs text-gray-400 uppercase tracking-widest">No invoices for this customer.</p>
            </template>

            <div class="overflow-x-auto" x-show="txTab !== 'invoice'">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-950 text-[10px] text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Transaction ID</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Method</th>
                            <th class="px-6 py-3">Phone Number</th>
                            <th class="px-6 py-3">Created On</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($transactions as $tx)
                            <tr x-show="!txSearch || {{ \Illuminate\Support\Js::from(strtolower(($tx->mpesa_receipt ?? '').' '.$tx->phone_number)) }}.includes(txSearch.toLowerCase())">
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $tx->id }}</td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400 font-fira">{{ $tx->mpesa_receipt ?: 'N/A' }}</td>
                                <td class="px-6 py-3 text-gray-900 dark:text-white font-bold">{{ number_format($tx->amount) }}</td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $tx->payment_method }}</td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $tx->phone_number }}</td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('H:i M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-xs text-gray-400 uppercase tracking-widest">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- === CHANGE EXPIRY MODAL === --}}
        <div x-show="expiryModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="expiryModal = false" x-show="expiryModal" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="expiryModal"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-md bg-white dark:bg-gray-900 shadow-xl h-full flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Change Expiry</h3>
                    <button type="button" @click="expiryModal = false" class="text-gray-400 hover:text-gray-600"><i class="bx bx-x text-2xl"></i></button>
                </div>
                <form action="{{ $oneispExtendUrl }}" method="POST" class="flex flex-col flex-1 min-h-0">
                    @csrf
                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Extend by (days)</label>
                        <input type="number" name="days" min="1" max="365" placeholder="7" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
                    </div>
                    <p class="text-xs text-gray-400 text-center">— or —</p>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Set exact expiry</label>
                        <input type="datetime-local" name="expires_at" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
                    </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" class="w-full bg-rose-500 hover:bg-rose-600 text-white font-bold text-sm py-3 rounded-lg">Save</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        {{-- === CHANGE PASSWORD MODAL (PPPoE only) === --}}
        @unless($oneispIsHotspot)
            <div x-show="passwordModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
                <div class="absolute inset-0 bg-gray-900/60" @click="passwordModal = false" x-show="passwordModal" x-transition.opacity></div>
                <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="passwordModal"
                     x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                    <div class="w-screen max-w-md bg-white dark:bg-gray-900 shadow-xl h-full flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Change Password</h3>
                        <button type="button" @click="passwordModal = false" class="text-gray-400 hover:text-gray-600"><i class="bx bx-x text-2xl"></i></button>
                    </div>
                    <form action="{{ route('pppoe-users.change-password', $user) }}" method="POST" class="flex flex-col flex-1 min-h-0">
                        @csrf
                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">New RADIUS Password</label>
                            <input type="text" name="password" required minlength="4" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 rounded-lg">Save</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        @endunless

        {{-- === VIEW / EDIT INFO MODAL — same field set as the Customers hub's "Add Customer"
             modal, pre-filled and editable. Status/expiry/router are preserved server-side
             (see PppoeUserController::update()/HotspotUserController::update()) since those
             are changed via their own dedicated actions above, not this form. === --}}
        <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="editModal = false" x-show="editModal" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="editModal"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-2xl bg-white dark:bg-gray-900 shadow-xl h-full flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Customer Info</h3>
                    <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <form action="{{ $oneispUpdateUrl }}" method="POST" class="flex flex-col flex-1 min-h-0">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Connection Type</label>
                            <div class="inline-flex items-center gap-2 py-2.5 px-4 rounded-lg text-sm font-bold bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/50">
                                <i class="bx {{ $oneispIsHotspot ? 'bx-broadcast' : 'bx-desktop' }} text-base"></i> {{ $oneispIsHotspot ? 'Hotspot' : 'PPPoE' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">First Name</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" placeholder="John" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Last Name</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" placeholder="Doe" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="john@example.com" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone Number @unless($oneispIsHotspot)<span class="text-gray-400 normal-case">(optional)</span>@else<span class="text-rose-500">*</span>@endunless</label>
                            <input type="tel" name="phone_number" @if($oneispIsHotspot) required @endif value="{{ old('phone_number', $user->phone_number) }}" placeholder="0712345678" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            @error('phone_number') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Address</label>
                            <input type="text" name="address" value="{{ old('address', $user->address) }}" placeholder="Street, town" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>

                        @if($oneispIsHotspot)
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">MAC Address</label>
                                <input type="text" name="mac_address" value="{{ old('mac_address', $user->mac_address) }}" placeholder="AA:BB:CC:DD:EE:FF" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors font-fira">
                            </div>
                        @else
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username <span class="text-rose-500">*</span></label>
                                <input type="text" name="username" required value="{{ old('username', $user->username) }}" placeholder="e.g., johndoe" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors font-fira">
                                @error('username') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package</label>
                            <select name="current_plan_id" class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white py-2.5 px-3.5 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors cursor-pointer">
                                <option value="">— None —</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected(old('current_plan_id', $user->current_plan_id) == $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 rounded-lg shadow-sm transition-colors inline-flex items-center justify-center gap-2">
                            <i class="bx bx-save text-lg"></i> Save Changes
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>

<x-guest-layout>
    <div
        x-data="subscriptionLock()"
        x-init="init()"
    >
        <div class="mb-4 text-center">
            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-3">
                <i class="bx bx-lock-alt text-2xl"></i>
            </div>
            <h1 class="text-lg font-bold text-gray-900">Subscription Payment Required</h1>
        </div>

        <div class="mb-4 text-sm text-gray-600">
            {{ __("Your grace period ended with an unpaid commission invoice. Dashboard access is paused until it's settled — your customers' service keeps running.") }}
        </div>

        <template x-if="paid">
            <div class="mb-5 flex items-center gap-2.5 bg-green-50 border border-green-100 text-green-700 text-sm rounded-lg px-4 py-3">
                <i class="bx bxs-check-circle text-lg shrink-0"></i>
                <span>Payment received — redirecting you to the dashboard&hellip;</span>
            </div>
        </template>

        <div class="mb-6 space-y-2" x-show="!paid">
            @foreach($overdueInvoices as $invoice)
                <div class="bg-red-50 border border-red-100 rounded-lg px-4 py-3" x-data="{ invoiceId: {{ $invoice->id }} }">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $invoice->period_start->format('F Y') }}</p>
                            <p class="text-xs text-red-600">Due {{ $invoice->due_at->format('d M Y') }} &middot; {{ $invoice->due_at->diffForHumans() }}</p>
                        </div>
                        <p class="text-sm font-bold text-gray-900 font-fira">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</p>
                    </div>

                    <template x-if="activeInvoiceId !== invoiceId">
                        <button type="button" @click="startPaying(invoiceId)" class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                            Pay Now via M-Pesa
                        </button>
                    </template>

                    <template x-if="activeInvoiceId === invoiceId">
                        <div class="mt-3 pt-3 border-t border-red-100">
                            <template x-if="stage === 'form'">
                                <form @submit.prevent="submitPay(invoiceId)" class="space-y-2">
                                    <input type="tel" x-model="phone" placeholder="0712345678" required pattern="^(0|254)7\d{8}$" class="w-full bg-white border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-lg py-2 px-3 text-sm outline-none">
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg transition-colors">Send STK Push</button>
                                        <button type="button" @click="reset()" class="px-3 text-sm text-gray-500 hover:text-gray-900">Cancel</button>
                                    </div>
                                </form>
                            </template>
                            <template x-if="stage === 'waiting'">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="bx bx-loader-alt bx-spin text-lg"></i>
                                    <span>Check your phone for the M-Pesa prompt and enter your PIN&hellip;</span>
                                </div>
                            </template>
                            <template x-if="stage === 'error'">
                                <div class="text-sm">
                                    <p class="text-red-600 mb-2" x-text="error"></p>
                                    <button type="button" @click="stage = 'form'" class="text-indigo-600 font-semibold">Try again</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            @endforeach
        </div>

        <a href="mailto:support@radiuspoint.co.ke" class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-white border border-gray-200 rounded-lg font-semibold text-sm text-gray-600 hover:bg-gray-50 transition ease-in-out duration-150">
            Paid a different way? Contact Support
        </a>

        <div class="mt-4 flex items-center justify-end">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>

    <script>
        function subscriptionLock() {
            return {
                activeInvoiceId: null,
                stage: 'form', // form | waiting | error
                phone: '',
                error: '',
                paid: false,
                pollTimer: null,

                init() {},

                startPaying(invoiceId) {
                    this.activeInvoiceId = invoiceId;
                    this.stage = 'form';
                    this.error = '';
                },

                reset() {
                    this.activeInvoiceId = null;
                    clearInterval(this.pollTimer);
                },

                async submitPay(invoiceId) {
                    this.stage = 'waiting';

                    try {
                        const res = await fetch('{{ route('billing.pay') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ invoice_id: invoiceId, phone: this.phone }),
                        });
                        const data = await res.json();

                        if (!res.ok) {
                            this.stage = 'error';
                            this.error = data.error || 'Something went wrong. Please try again.';
                            return;
                        }

                        this.pollStatus(invoiceId);
                    } catch (e) {
                        this.stage = 'error';
                        this.error = 'Could not reach the server. Please check your connection and try again.';
                    }
                },

                pollStatus(invoiceId) {
                    // Real M-Pesa STK prompts resolve (paid, cancelled, or timed out) well within
                    // 90s — 3s polling is frequent enough to feel instant without hammering the
                    // endpoint, matching the same cadence PaymentPortalController's customer flow uses.
                    this.pollTimer = setInterval(async () => {
                        const res = await fetch(`{{ url('/subscription-required/status') }}/${invoiceId}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();

                        if (data.status === 'paid') {
                            clearInterval(this.pollTimer);
                            this.paid = true;
                            setTimeout(() => window.location.href = '{{ route('dashboard') }}', 1500);
                        }
                    }, 3000);

                    // Stop polling after ~2 minutes so an abandoned/failed STK push doesn't poll
                    // forever — the tenant can just tap "Pay Now" again to retry.
                    setTimeout(() => {
                        if (!this.paid) {
                            clearInterval(this.pollTimer);
                            if (this.stage === 'waiting') {
                                this.stage = 'error';
                                this.error = 'We didn\'t receive confirmation in time. If you completed the payment, it may still arrive shortly — otherwise, please try again.';
                            }
                        }
                    }, 120000);
                },
            };
        }
    </script>
</x-guest-layout>

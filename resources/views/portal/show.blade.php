<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi Payment</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Figtree', 'ui-sans-serif', 'system-ui'] } } } }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="font-sans bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div x-data="paymentPortal()" class="w-full max-w-md bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white text-center">
            <i class="bx bx-wifi text-4xl"></i>
            <h1 class="text-xl font-bold mt-2">{{ $tenant->company_name }}</h1>
            <p class="text-sm text-blue-100">Buy internet access</p>
        </div>

        <div class="p-6">
            <!-- Step 1: pick a plan + enter phone -->
            <template x-if="step === 'select'">
                <div class="space-y-4">
                    <div class="space-y-2">
                        @forelse($plans as $plan)
                            <button type="button" @click="selectedPlan = {{ $plan->id }}"
                                class="w-full text-left border rounded-xl p-4 transition-colors"
                                :class="selectedPlan === {{ $plan->id }} ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300'">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $plan->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $plan->duration_value }} {{ ucfirst($plan->duration_unit) }} &middot; {{ $plan->speed_limit }}</p>
                                    </div>
                                    <p class="font-bold text-blue-600">KES {{ number_format($plan->price) }}</p>
                                </div>
                            </button>
                        @empty
                            <p class="text-sm text-gray-500 text-center py-6">No packages are available on this network yet.</p>
                        @endforelse
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Phone Number (M-Pesa)</label>
                        <input type="tel" x-model="phone" placeholder="0712345678" class="w-full border border-gray-200 rounded-lg py-3 px-4 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <p x-show="error" x-text="error" class="text-sm text-red-500"></p>

                    <button type="button" @click="pay()" :disabled="!selectedPlan || !phone || paying"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-3 rounded-lg transition-colors">
                        <span x-show="!paying">Pay Now</span>
                        <span x-show="paying">Sending request...</span>
                    </button>
                </div>
            </template>

            <!-- Step 2: waiting for STK push completion -->
            <template x-if="step === 'waiting'">
                <div class="text-center py-8 space-y-4">
                    <i class="bx bx-loader-alt bx-spin text-4xl text-blue-600"></i>
                    <p class="font-bold text-gray-900">Check your phone</p>
                    <p class="text-sm text-gray-500">Enter your M-Pesa PIN to complete the payment. This page will update automatically.</p>
                </div>
            </template>

            <!-- Step 3: success -->
            <template x-if="step === 'success'">
                <div class="text-center py-4 space-y-4">
                    <i class="bx bxs-check-circle text-4xl text-green-500"></i>
                    <p class="font-bold text-gray-900">You're connected!</p>
                    <div class="bg-gray-50 rounded-lg p-4 text-left space-y-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Username</p>
                        <p class="font-bold font-mono" x-text="username"></p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mt-2">Password</p>
                        <p class="font-bold font-mono" x-text="password"></p>
                    </div>
                    <p class="text-xs text-gray-500">Open your WiFi login page and enter these details to get online.</p>
                </div>
            </template>

            <!-- Step 4: failed -->
            <template x-if="step === 'failed'">
                <div class="text-center py-8 space-y-4">
                    <i class="bx bxs-x-circle text-4xl text-red-500"></i>
                    <p class="font-bold text-gray-900">Payment failed</p>
                    <p class="text-sm text-gray-500" x-text="error || 'The payment was not completed. Please try again.'"></p>
                    <button type="button" @click="reset()" class="w-full bg-gray-900 text-white font-bold py-3 rounded-lg">Try Again</button>
                </div>
            </template>
        </div>
    </div>

    <script>
        function paymentPortal() {
            return {
                step: 'select',
                selectedPlan: null,
                phone: '',
                paying: false,
                error: null,
                username: null,
                password: null,
                pollTimer: null,

                async pay() {
                    this.paying = true;
                    this.error = null;

                    try {
                        const response = await fetch("{{ route('portal.pay', $router) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                plan_id: this.selectedPlan,
                                phone: this.phone,
                                mac: @json($mac),
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.error = data.error || 'Something went wrong. Please try again.';
                            this.paying = false;
                            return;
                        }

                        this.step = 'waiting';
                        this.pollStatus(data.transaction_id);
                    } catch (e) {
                        this.error = 'Network error. Please try again.';
                        this.paying = false;
                    }
                },

                pollStatus(transactionId) {
                    const url = "{{ route('portal.status', [$router, '__ID__']) }}".replace('__ID__', transactionId);

                    this.pollTimer = setInterval(async () => {
                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await response.json();

                        if (data.status === 'success') {
                            clearInterval(this.pollTimer);
                            this.username = data.username;
                            this.password = data.password;
                            this.step = 'success';
                        } else if (data.status === 'failed') {
                            clearInterval(this.pollTimer);
                            this.step = 'failed';
                        }
                    }, 3000);
                },

                reset() {
                    this.step = 'select';
                    this.paying = false;
                    this.error = null;
                    if (this.pollTimer) clearInterval(this.pollTimer);
                },
            };
        }
    </script>
</body>
</html>

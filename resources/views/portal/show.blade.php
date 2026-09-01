<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi Payment</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-body-secondary p-3">

    <div class="card shadow-lg" style="width:100%;max-width:28rem;border-radius:1rem;overflow:hidden">
        <div class="p-4 text-white text-center" style="background:linear-gradient(to right, var(--tblr-primary), var(--tblr-indigo))">
            <i class="ti ti-wifi fs-1"></i>
            <h1 class="h4 mt-2 mb-0">{{ $tenant->company_name }}</h1>
            <p class="text-white-50 small mb-0">Buy internet access</p>
        </div>

        <div class="card-body p-4">
            {{-- Step 1: pick a plan + enter phone --}}
            <div id="rp-portal-step-select">
                <div class="d-flex flex-column gap-2 mb-3">
                    @forelse($plans as $plan)
                        <button type="button" class="btn text-start p-3" data-rp-plan-option data-rp-plan-id="{{ $plan->id }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="fw-bold mb-0">{{ $plan->name }}</p>
                                    <p class="text-muted small mb-0">{{ $plan->duration_value }} {{ ucfirst($plan->duration_unit) }} &middot; {{ $plan->speed_limit }}</p>
                                </div>
                                <p class="fw-bold text-primary mb-0">KES {{ number_format($plan->price) }}</p>
                            </div>
                        </button>
                    @empty
                        <p class="text-muted text-center py-4 mb-0">No packages are available on this network yet.</p>
                    @endforelse
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number (M-Pesa)</label>
                    <input type="tel" id="rp-portal-phone" placeholder="0712345678" class="form-control">
                </div>

                <p class="text-danger small d-none" id="rp-portal-error"></p>

                <button type="button" id="rp-portal-pay-btn" disabled class="btn btn-primary w-100">
                    <span id="rp-portal-pay-label">Pay Now</span>
                </button>
            </div>

            {{-- Step 2: waiting for STK push completion --}}
            <div id="rp-portal-step-waiting" class="d-none text-center py-4">
                <i class="ti ti-loader-2 icon-spin fs-1 text-primary"></i>
                <p class="fw-bold mt-3 mb-1">Check your phone</p>
                <p class="text-muted small mb-0">Enter your M-Pesa PIN to complete the payment. This page will update automatically.</p>
            </div>

            {{-- Step 3: success --}}
            <div id="rp-portal-step-success" class="d-none text-center py-2">
                <i class="ti ti-circle-check-filled fs-1 text-success"></i>
                <p class="fw-bold mt-3 mb-3">You're connected!</p>
                <div class="bg-body-secondary rounded p-3 text-start mb-3">
                    <p class="text-uppercase text-muted small mb-0">Username</p>
                    <p class="fw-bold font-monospace" id="rp-portal-username"></p>
                    <p class="text-uppercase text-muted small mb-0 mt-2">Password</p>
                    <p class="fw-bold font-monospace mb-0" id="rp-portal-password"></p>
                </div>
                <p class="text-muted small mb-0">Open your WiFi login page and enter these details to get online.</p>
            </div>

            {{-- Step 4: failed --}}
            <div id="rp-portal-step-failed" class="d-none text-center py-4">
                <i class="ti ti-circle-x-filled fs-1 text-danger"></i>
                <p class="fw-bold mt-3 mb-1">Payment failed</p>
                <p class="text-muted small mb-3" id="rp-portal-failed-message">The payment was not completed. Please try again.</p>
                <button type="button" id="rp-portal-retry-btn" class="btn btn-dark w-100">Try Again</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var mac = @json($mac);
            var selectedPlan = null;
            var pollTimer = null;

            var steps = {
                select: document.getElementById('rp-portal-step-select'),
                waiting: document.getElementById('rp-portal-step-waiting'),
                success: document.getElementById('rp-portal-step-success'),
                failed: document.getElementById('rp-portal-step-failed'),
            };
            var phoneInput = document.getElementById('rp-portal-phone');
            var errorEl = document.getElementById('rp-portal-error');
            var payBtn = document.getElementById('rp-portal-pay-btn');
            var payLabel = document.getElementById('rp-portal-pay-label');

            function showStep(name) {
                Object.keys(steps).forEach(function (key) {
                    steps[key].classList.toggle('d-none', key !== name);
                });
            }

            function syncPayButton() {
                payBtn.disabled = !selectedPlan || !phoneInput.value;
            }

            document.querySelectorAll('[data-rp-plan-option]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectedPlan = btn.getAttribute('data-rp-plan-id');
                    document.querySelectorAll('[data-rp-plan-option]').forEach(function (b) {
                        b.classList.toggle('btn-outline-primary', b === btn);
                        b.classList.toggle('active', b === btn);
                    });
                    syncPayButton();
                });
            });

            phoneInput.addEventListener('input', syncPayButton);

            function setError(message) {
                if (message) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('d-none');
                } else {
                    errorEl.classList.add('d-none');
                }
            }

            async function pay() {
                payBtn.disabled = true;
                payLabel.textContent = 'Sending request...';
                setError(null);

                try {
                    var response = await fetch("{{ route('portal.pay', $router) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({
                            plan_id: selectedPlan,
                            phone: phoneInput.value,
                            mac: mac,
                        }),
                    });

                    var data = await response.json();

                    if (!response.ok) {
                        setError(data.error || 'Something went wrong. Please try again.');
                        payBtn.disabled = false;
                        payLabel.textContent = 'Pay Now';
                        return;
                    }

                    showStep('waiting');
                    pollStatus(data.transaction_id);
                } catch (e) {
                    setError('Network error. Please try again.');
                    payBtn.disabled = false;
                    payLabel.textContent = 'Pay Now';
                }
            }

            function pollStatus(transactionId) {
                var url = "{{ route('portal.status', [$router, '__ID__']) }}".replace('__ID__', transactionId);

                pollTimer = setInterval(async function () {
                    var response = await fetch(url, { headers: { Accept: 'application/json' } });
                    var data = await response.json();

                    if (data.status === 'success') {
                        clearInterval(pollTimer);
                        document.getElementById('rp-portal-username').textContent = data.username;
                        document.getElementById('rp-portal-password').textContent = data.password;
                        showStep('success');
                    } else if (data.status === 'failed') {
                        clearInterval(pollTimer);
                        showStep('failed');
                    }
                }, 3000);
            }

            payBtn.addEventListener('click', pay);

            document.getElementById('rp-portal-retry-btn').addEventListener('click', function () {
                selectedPlan = null;
                payBtn.disabled = true;
                payLabel.textContent = 'Pay Now';
                setError(null);
                if (pollTimer) clearInterval(pollTimer);
                document.querySelectorAll('[data-rp-plan-option]').forEach(function (b) { b.classList.remove('btn-outline-primary', 'active'); });
                showStep('select');
            });
        })();
    </script>
</body>
</html>

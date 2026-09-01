<x-guest-layout>
    <div class="mb-3 text-center">
        <span class="avatar bg-red-lt mb-3"><i class="ti ti-lock fs-2"></i></span>
        <h1 class="h3 mb-0">Subscription Payment Required</h1>
    </div>

    <div class="mb-3 text-muted small">
        {{ __("Your grace period ended with an unpaid commission invoice. Dashboard access is paused until it's settled — your customers' service keeps running.") }}
    </div>

    <div class="alert alert-success d-none" id="rp-billing-paid">
        <i class="ti ti-circle-check-filled icon"></i>
        <span>Payment received — redirecting you to the dashboard&hellip;</span>
    </div>

    <div class="mb-4" id="rp-billing-invoices">
        @foreach($overdueInvoices as $invoice)
            <div class="border border-danger-subtle bg-red-lt rounded p-3 mb-2" data-rp-invoice="{{ $invoice->id }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="fw-bold mb-0">{{ $invoice->period_start->format('F Y') }}</p>
                        <p class="text-danger small mb-0">Due {{ $invoice->due_at->format('d M Y') }} &middot; {{ $invoice->due_at->diffForHumans() }}</p>
                    </div>
                    <p class="fw-bold font-monospace mb-0">{{ $tenant->currency_symbol ?? 'KES' }} {{ number_format($invoice->amount_due, 2) }}</p>
                </div>

                <button type="button" class="btn btn-primary btn-sm w-100 mt-3" data-rp-start-pay>
                    Pay Now via M-Pesa
                </button>

                <div class="mt-3 pt-3 border-top border-danger-subtle" data-rp-pay-panel style="display:none">
                    <form data-rp-pay-form class="d-flex flex-column gap-2">
                        <input type="tel" name="phone" placeholder="0712345678" required pattern="^(0|254)7\d{8}$" class="form-control form-control-sm">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">Send STK Push</button>
                            <button type="button" class="btn btn-link btn-sm" data-rp-cancel-pay>Cancel</button>
                        </div>
                    </form>
                    <div class="d-flex align-items-center gap-2 small text-muted" data-rp-pay-waiting style="display:none">
                        <i class="ti ti-loader-2 icon-spin"></i>
                        <span>Check your phone for the M-Pesa prompt and enter your PIN&hellip;</span>
                    </div>
                    <div data-rp-pay-error style="display:none">
                        <p class="text-danger small mb-2" data-rp-pay-error-text></p>
                        <button type="button" class="btn btn-link btn-sm p-0" data-rp-retry-pay>Try again</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <a href="mailto:support@radiuspoint.co.ke" class="btn w-100">
        Paid a different way? Contact Support
    </a>

    <div class="mt-3 d-flex justify-content-end">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-muted">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>

    <script>
        (function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var paidBanner = document.getElementById('rp-billing-paid');
            var invoicesWrap = document.getElementById('rp-billing-invoices');
            var paid = false;

            function setStage(card, stage, errorText) {
                card.querySelector('[data-rp-pay-form]').style.display = stage === 'form' ? '' : 'none';
                card.querySelector('[data-rp-pay-waiting]').style.display = stage === 'waiting' ? 'flex' : 'none';
                var errorEl = card.querySelector('[data-rp-pay-error]');
                errorEl.style.display = stage === 'error' ? '' : 'none';
                if (stage === 'error') errorEl.querySelector('[data-rp-pay-error-text]').textContent = errorText || 'Something went wrong. Please try again.';
            }

            function pollStatus(card, invoiceId) {
                // Real M-Pesa STK prompts resolve (paid, cancelled, or timed out) well within
                // 90s — 3s polling is frequent enough to feel instant without hammering the
                // endpoint, matching the same cadence PaymentPortalController's customer flow uses.
                var pollTimer = setInterval(async function () {
                    var res = await fetch("{{ url('/subscription-required/status') }}/" + invoiceId, {
                        headers: { Accept: 'application/json' },
                    });
                    var data = await res.json();

                    if (data.status === 'paid') {
                        clearInterval(pollTimer);
                        paid = true;
                        invoicesWrap.style.display = 'none';
                        paidBanner.classList.remove('d-none');
                        setTimeout(function () { window.location.href = '{{ route('dashboard') }}'; }, 1500);
                    }
                }, 3000);

                // Stop polling after ~2 minutes so an abandoned/failed STK push doesn't poll
                // forever — the tenant can just tap "Pay Now" again to retry.
                setTimeout(function () {
                    if (!paid) {
                        clearInterval(pollTimer);
                        setStage(card, 'error', "We didn't receive confirmation in time. If you completed the payment, it may still arrive shortly — otherwise, please try again.");
                    }
                }, 120000);
            }

            document.querySelectorAll('[data-rp-invoice]').forEach(function (card) {
                var invoiceId = card.getAttribute('data-rp-invoice');
                var startBtn = card.querySelector('[data-rp-start-pay]');
                var panel = card.querySelector('[data-rp-pay-panel]');
                var form = card.querySelector('[data-rp-pay-form]');

                startBtn.addEventListener('click', function () {
                    startBtn.style.display = 'none';
                    panel.style.display = '';
                    setStage(card, 'form');
                });

                card.querySelector('[data-rp-cancel-pay]').addEventListener('click', function () {
                    startBtn.style.display = '';
                    panel.style.display = 'none';
                });

                card.querySelector('[data-rp-retry-pay]').addEventListener('click', function () {
                    setStage(card, 'form');
                });

                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    setStage(card, 'waiting');

                    try {
                        var res = await fetch('{{ route('billing.pay') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ invoice_id: invoiceId, phone: form.phone.value }),
                        });
                        var data = await res.json();

                        if (!res.ok) {
                            setStage(card, 'error', data.error);
                            return;
                        }

                        pollStatus(card, invoiceId);
                    } catch (e) {
                        setStage(card, 'error', 'Could not reach the server. Please check your connection and try again.');
                    }
                });
            });
        })();
    </script>
</x-guest-layout>

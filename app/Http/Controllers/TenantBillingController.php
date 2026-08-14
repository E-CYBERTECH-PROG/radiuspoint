<?php

namespace App\Http\Controllers;

use App\Models\MpesaSetting;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * A tenant's view of, and self-service payment for, their own RadiusPoint commission invoices
 * — not to be confused with BillingController, which handles M-Pesa STK push callbacks for the
 * tenant's *customers* paying for hotspot/PPPoE plans. Changing tier/status directly is still a
 * platform-admin action (PlatformAdmin\TenantController); this only handles paying what's owed.
 */
class TenantBillingController extends Controller
{
    public function edit()
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $invoices = $tenant->invoices()->withoutGlobalScope('tenant')->latest('period_start')->get();

        return view('billing.edit', compact('tenant', 'invoices'));
    }

    /**
     * A standalone, print-friendly invoice — same pattern as VoucherController::print(), its
     * own bare <html> with a @media print rule, no sidebar chrome to accidentally print too.
     */
    public function printInvoice(int $invoice)
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $invoice = TenantInvoice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);

        return view('billing.print', compact('tenant', 'invoice'));
    }

    /**
     * Shown instead of the dashboard once a commission invoice has gone unpaid past its grace
     * period (see EnsureTenantSubscribed) — this route itself sits outside that middleware so
     * a locked-out tenant can still reach it.
     */
    public function locked()
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $overdueInvoices = $tenant->invoices()->withoutGlobalScope('tenant')
            ->where('status', 'pending')->where('due_at', '<', now())
            ->latest('period_start')->get();

        return view('billing.locked', compact('tenant', 'overdueInvoices'));
    }

    /**
     * Triggers a real M-Pesa STK push against RadiusPoint's *own* till/paybill (configured by a
     * platform admin at /mpesa-settings, stored under tenant_id=config('billing.platform_tenant_id'))
     * — reuses the exact same MpesaSetting/MpesaService infrastructure a tenant's own customers
     * pay through, just pointed at the platform's account instead of the tenant's.
     */
    public function pay(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer',
            'phone' => ['required', 'string', 'regex:/^(?:254|0)7\d{8}$/'],
        ]);

        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $invoice = TenantInvoice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->findOrFail($request->invoice_id);

        $phone = $request->phone;
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }

        // Same slot 1 (primary) / slot 2 (backup) fallback pattern as PaymentPortalController.
        $gateways = MpesaSetting::withoutGlobalScope('tenant')
            ->where('tenant_id', config('billing.platform_tenant_id'))
            ->whereIn('slot', [1, 2])
            ->orderBy('slot')
            ->get()
            ->filter(fn ($s) => $this->isConfigured($s));

        if ($gateways->isEmpty()) {
            return response()->json(['error' => 'Online payment isn\'t set up yet. Please contact support to pay.'], 422);
        }

        $response = null;
        foreach ($gateways as $settings) {
            $response = $this->attemptStkPush($settings, $phone, $invoice);

            if ($response !== null) {
                break;
            }
        }

        if ($response === null) {
            return response()->json(['error' => 'Could not start the M-Pesa payment. Please try again.'], 422);
        }

        $invoice->update([
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Polled by the locked page while an STK push is in flight — flips to "paid" the moment
     * PlatformBillingController::handleCallback() processes Safaricom's confirmation, at which
     * point the frontend redirects to the dashboard (which now passes EnsureTenantSubscribed).
     */
    public function status(Request $request, int $invoice)
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $invoice = TenantInvoice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);

        return response()->json(['status' => $invoice->status]);
    }

    /**
     * is_active alone isn't enough — a platform admin can flip the toggle on before actually
     * entering real Daraja credentials, which would otherwise surface as a raw TypeError instead
     * of a clear message (same guard as PaymentPortalController::isConfigured()).
     */
    private function isConfigured(?MpesaSetting $settings): bool
    {
        return $settings && $settings->is_active
            && filled($settings->consumer_key)
            && filled($settings->consumer_secret)
            && filled($settings->passkey)
            && filled($settings->shortcode);
    }

    private function attemptStkPush(MpesaSetting $settings, string $phone, TenantInvoice $invoice): ?array
    {
        try {
            $mpesa = new MpesaService($settings);
            $response = $mpesa->stkPush(
                $phone,
                (float) $invoice->amount_due,
                'RadiusPoint-Invoice-'.$invoice->id,
                'RadiusPoint commission — '.$invoice->period_start->format('F Y'),
                route('api.platform-mpesa.callback')
            );
        } catch (\Throwable $e) {
            Log::error('Platform commission STK push threw an exception', ['invoice_id' => $invoice->id, 'slot' => $settings->slot, 'error' => $e->getMessage()]);

            return null;
        }

        if (! isset($response['CheckoutRequestID'])) {
            Log::warning('Platform commission STK push failed to initiate', ['invoice_id' => $invoice->id, 'slot' => $settings->slot] + $response);

            return null;
        }

        return $response;
    }
}

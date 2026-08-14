<?php

namespace App\Http\Controllers;

use App\Models\TenantInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The endpoint Safaricom Daraja pings once a tenant completes (or cancels) an STK push for
 * their own commission invoice — mirrors BillingController::handleCallback() exactly, just
 * keyed to a TenantInvoice instead of a customer-facing Transaction. Marking an invoice paid
 * here is what makes EnsureTenantSubscribed unlock the dashboard on the tenant's very next
 * request, with zero platform-admin involvement.
 */
class PlatformBillingController extends Controller
{
    public function handleCallback(Request $request)
    {
        Log::info('Platform commission M-Pesa callback received:', $request->all());

        $callbackData = $request->input('Body.stkCallback');
        $resultCode = $callbackData['ResultCode'] ?? null;
        $resultDesc = $callbackData['ResultDesc'] ?? null;
        $checkoutRequestId = $callbackData['CheckoutRequestID'] ?? null;

        $invoice = TenantInvoice::withoutGlobalScope('tenant')
            ->where('checkout_request_id', $checkoutRequestId)
            ->first();

        if (! $invoice) {
            Log::warning("Platform commission M-Pesa callback received for unknown CheckoutRequestID: {$checkoutRequestId}");

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
        }

        if ($resultCode == 0) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } else {
            // Leave it pending, not "failed" — invoices only have pending/paid (see migration);
            // the tenant simply retries Pay Now, same as a customer would on a declined STK push.
            Log::warning("Platform commission STK push failed for invoice {$invoice->id}: {$resultDesc}");
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
}

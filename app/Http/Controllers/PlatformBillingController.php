<?php

namespace App\Http\Controllers;

use App\Models\TenantInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * M-Pesa callback for a tenant's platform commission invoice. Marking an invoice paid here
 * lets EnsureTenantSubscribed unlock the dashboard on the tenant's next request.
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
            // Leave it pending (invoices only have pending/paid); the tenant retries Pay Now.
            Log::warning("Platform commission STK push failed for invoice {$invoice->id}: {$resultDesc}");
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
}

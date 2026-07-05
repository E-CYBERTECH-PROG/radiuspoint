<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Services\RadiusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * Handle the STK Push Callback from Safaricom.
     */
    public function handleCallback(Request $request)
    {
        Log::info('M-Pesa Callback Received:', $request->all());

        $callbackData = $request->input('Body.stkCallback');
        $resultCode = $callbackData['ResultCode'] ?? null;
        $resultDesc = $callbackData['ResultDesc'] ?? null;
        $checkoutRequestId = $callbackData['CheckoutRequestID'] ?? null;

        $transaction = Transaction::withoutGlobalScope('tenant')
            ->where('checkout_request_id', $checkoutRequestId)
            ->first();

        if (! $transaction) {
            Log::warning("M-Pesa callback received for unknown CheckoutRequestID: {$checkoutRequestId}");

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
        }

        if ($resultCode == 0) {
            $mpesaReceiptNumber = null;

            foreach ($callbackData['CallbackMetadata']['Item'] ?? [] as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item['Value'];
                }
            }

            $transaction->update([
                'mpesa_receipt' => $mpesaReceiptNumber,
                'status' => 'success',
            ]);

            $this->activateHotspotUser($transaction);
        } else {
            $transaction->update(['status' => 'failed']);
            Log::warning("M-Pesa Transaction Failed: {$resultDesc}");
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    /**
     * Provision the paying customer as an active HotspotUser and sync real RADIUS credentials.
     */
    private function activateHotspotUser(Transaction $transaction): void
    {
        $plan = $transaction->plan_id ? Plan::withoutGlobalScope('tenant')->find($transaction->plan_id) : null;

        if (! $plan) {
            return;
        }

        $hotspotUser = HotspotUser::withoutGlobalScope('tenant')->create([
            'tenant_id' => $transaction->tenant_id,
            'phone_number' => $transaction->phone_number,
            'current_plan_id' => $plan->id,
            'status' => 'active',
            'expires_at' => $plan->expiresAt(),
        ]);

        $transaction->update(['hotspot_user_id' => $hotspotUser->id]);

        $password = Str::password(10);
        RadiusSyncService::sync($hotspotUser->phone_number, $password, $plan->speed_limit);

        SmsMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $transaction->tenant_id,
            'phone_number' => $hotspotUser->phone_number,
            'message' => "Payment received for {$plan->name}. Login with Username: {$hotspotUser->phone_number} Password: {$password}",
            'status' => 'queued',
            'initiator' => 'M-Pesa Payment',
        ]);

        Log::info("HotspotUser {$hotspotUser->phone_number} provisioned successfully with {$plan->speed_limit} limits.");
    }
}

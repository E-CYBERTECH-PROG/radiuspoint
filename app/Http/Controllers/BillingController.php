<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use App\Services\ExpiredBlockService;
use App\Services\RadiusSyncService;
use App\Services\SmsTriggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingController extends Controller
{
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
            'mac_address' => $transaction->mac_address,
            'current_plan_id' => $plan->id,
            'current_router_id' => $transaction->router_id,
            'status' => 'active',
            'expires_at' => $plan->expiresAt(),
        ]);

        $transaction->update(['hotspot_user_id' => $hotspotUser->id]);

        // Two valid RADIUS credentials get synced for a fresh auto-purchase, both against the
        // same plan/expiry (see HotspotUser::radiusUsernames()):
        //  1. phone_number + a generated password — the standing credential this account is
        //     created with, for the customer to log back in with later (self-service phone
        //     lookup, or typed directly at the router). Same shape as a manually-created
        //     account's credential.
        //  2. The M-Pesa receipt itself, username=password=code — what the automatic
        //     post-purchase reconnect uses (PaymentPortalController::status()) and what
        //     shows on the router's active-users list, same scheme as vouchers
        //     (VoucherController::generate()).
        // phone_number is also the app-level identity (SMS, records, dashboards) regardless.
        $code = $transaction->mpesa_receipt;
        $password = Str::password(10);
        $router = Router::withoutGlobalScope('tenant')->find($transaction->router_id);

        foreach ([$hotspotUser->phone_number => $password, $code => $code] as $username => $pass) {
            RadiusSyncService::sync($username, $pass, $plan->speed_limit);
            RadiusSyncService::setExpiryWindow($username, $hotspotUser->expires_at);
            ExpiredBlockService::clear($router, $username);
        }

        SmsTriggerService::fire(
            $transaction->tenant_id, 'hotspot_purchase_confirmed', $hotspotUser->phone_number,
            ['name' => $hotspotUser->phone_number, 'plan' => $plan->name, 'password' => $password, 'code' => $code, 'expires_at' => $hotspotUser->expires_at?->format('d M Y H:i')],
            fallbackMessage: "Payment received for {$plan->name}. Login with Username: {$hotspotUser->phone_number} Password: {$password} (or code: {$code})"
        );

        Log::info("HotspotUser {$hotspotUser->phone_number} provisioned successfully with {$plan->speed_limit} limits (code: {$code}).");
    }
}

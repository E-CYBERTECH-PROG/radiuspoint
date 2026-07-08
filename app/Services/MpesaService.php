<?php

namespace App\Services;

use App\Models\MpesaSetting;
use App\Models\Tenant;
use App\Notifications\MpesaGatewayDown;
use App\Notifications\MpesaGatewayRecovered;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class MpesaService
{
    /**
     * Consecutive stkPush() failures before declaring the gateway "down" — high enough that a
     * single flaky request doesn't trigger a false alarm, low enough to catch a real Safaricom
     * outage within a handful of customer payment attempts.
     */
    private const FAILURE_THRESHOLD = 3;

    public function __construct(private MpesaSetting $settings)
    {
        //
    }

    private function baseUrl(): string
    {
        return $this->settings->environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth($this->settings->consumer_key, $this->settings->consumer_secret)
            ->get($this->baseUrl().'/oauth/v1/generate?grant_type=client_credentials');

        return $response->json('access_token');
    }

    public function stkPush(string $phone, float $amount, string $accountReference, string $description, string $callbackUrl): array
    {
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->settings->shortcode.$this->settings->passkey.$timestamp);
        $transactionType = $this->settings->gateway_type === 'till' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline';

        $response = Http::withToken($this->getAccessToken())
            ->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $this->settings->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $transactionType,
                'Amount' => (int) $amount,
                'PartyA' => $phone,
                'PartyB' => $this->settings->shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $description,
            ]);

        $result = $response->json() ?? [];
        $this->recordHealth(isset($result['CheckoutRequestID']));

        return $result;
    }

    /**
     * Tracks consecutive failures and fires an alert once the threshold is crossed — not on
     * every single failure, which would spam a notification per failed customer payment attempt
     * during a real outage. Recovery fires once, on the first success after being "down."
     */
    private function recordHealth(bool $success): void
    {
        $wasDown = $this->settings->consecutive_failures >= self::FAILURE_THRESHOLD;

        if ($success) {
            $this->settings->update(['consecutive_failures' => 0, 'last_checked_at' => now()]);

            if ($wasDown) {
                $this->notifyTenant(new MpesaGatewayRecovered());
            }

            return;
        }

        $count = $this->settings->consecutive_failures + 1;
        $this->settings->update(['consecutive_failures' => $count, 'last_checked_at' => now()]);

        if ($count === self::FAILURE_THRESHOLD) {
            $this->notifyTenant(new MpesaGatewayDown());
        }
    }

    private function notifyTenant($notification): void
    {
        $tenant = Tenant::find($this->settings->tenant_id);
        if ($tenant) {
            Notification::send($tenant->users, $notification);
        }
    }
}

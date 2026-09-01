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
     * Consecutive stkPush() failures before declaring the gateway "down".
     */
    private const FAILURE_THRESHOLD = 3;

    private MpesaSetting $destination;

    /**
     * $credentials is whichever Daraja app actually authenticates the push (its shortcode +
     * passkey build the Password, and its consumer key/secret get the OAuth token). $destination
     * is where the money should land — defaults to $credentials itself, which reproduces the old
     * one-gateway-does-both behavior for every existing caller. Use MpesaService::for() when
     * those two need to differ.
     */
    public function __construct(private MpesaSetting $credentials, ?MpesaSetting $destination = null)
    {
        $this->destination = $destination ?? $credentials;
    }

    /**
     * Resolves the Daraja app that authenticates the push: $destination's own if it's a
     * fully configured app, otherwise RadiusPoint's shared platform gateway — so a tenant only
     * has to say where their money should land (till/paybill/bank) rather than register and run
     * their own Daraja app just to collect it. The destination's own wallet fields still decide
     * PartyB/TransactionType/AccountReference regardless of which app authenticates.
     */
    public static function for(MpesaSetting $destination): self
    {
        if (self::hasFullCredentials($destination)) {
            return new self($destination, $destination);
        }

        $platform = MpesaSetting::withoutGlobalScope('tenant')
            ->where('tenant_id', config('billing.platform_tenant_id'))
            ->where('slot', 1)
            ->first();

        return new self($platform ?? $destination, $destination);
    }

    private static function hasFullCredentials(MpesaSetting $settings): bool
    {
        return filled($settings->consumer_key)
            && filled($settings->consumer_secret)
            && filled($settings->passkey)
            && filled($settings->shortcode);
    }

    private function baseUrl(): string
    {
        return $this->credentials->environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth($this->credentials->consumer_key, $this->credentials->consumer_secret)
            ->get($this->baseUrl().'/oauth/v1/generate?grant_type=client_credentials');

        return $response->json('access_token');
    }

    public function stkPush(string $phone, float $amount, string $accountReference, string $description, string $callbackUrl): array
    {
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->credentials->shortcode.$this->credentials->passkey.$timestamp);
        [$transactionType, $partyB, $finalAccountRef] = $this->resolveDestination($accountReference);

        $response = Http::withToken($this->getAccessToken())
            ->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $this->credentials->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $transactionType,
                'Amount' => (int) $amount,
                'PartyA' => $phone,
                'PartyB' => $partyB,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $finalAccountRef,
                'TransactionDesc' => $description,
            ]);

        $result = $response->json() ?? [];
        $this->recordHealth(isset($result['CheckoutRequestID']));

        return $result;
    }

    /**
     * Where the money actually lands, independent of which app authenticated the push. A 'till'
     * or 'paybill' destination reuses the row's own `shortcode` column (till number / paybill
     * number respectively); 'bank' routes through the bank's own paybill with the tenant's bank
     * account number as the reference, since that's how a Paybill-to-bank deposit is matched on
     * the bank's side. $reference is always the specific transaction's own identifier (phone
     * number, invoice id, etc.) — it wins over the destination's saved account_number, which is
     * only a fallback for a paybill destination when no per-transaction reference is given.
     *
     * @return array{0: string, 1: string, 2: string} [TransactionType, PartyB, AccountReference]
     */
    private function resolveDestination(string $reference): array
    {
        $dest = $this->destination;

        if ($dest->gateway_type === 'till' && filled($dest->shortcode)) {
            return ['CustomerBuyGoodsOnline', $dest->shortcode, $reference];
        }

        if ($dest->gateway_type === 'bank' && filled($dest->bank_paybill)) {
            return ['CustomerPayBillOnline', $dest->bank_paybill, $dest->bank_account_number ?: $reference];
        }

        if ($dest->gateway_type === 'paybill' && filled($dest->shortcode)) {
            return ['CustomerPayBillOnline', $dest->shortcode, $reference ?: $dest->account_number];
        }

        // Destination row has no usable wallet info of its own — settle on whichever
        // shortcode is authenticating, same as when credentials and destination are the same row.
        return [
            $this->credentials->gateway_type === 'till' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
            $this->credentials->shortcode,
            $reference,
        ];
    }

    /**
     * Tracks consecutive failures and fires an alert once the threshold is crossed. Tracked
     * against the authenticating app ($credentials), since that's the gateway that's actually
     * making the Daraja calls and can actually go "down" — a destination-only row (no Daraja app
     * of its own) has no health of its own to track.
     * Recovery fires once, on the first success after being "down".
     */
    private function recordHealth(bool $success): void
    {
        $wasDown = $this->credentials->consecutive_failures >= self::FAILURE_THRESHOLD;

        if ($success) {
            $this->credentials->update(['consecutive_failures' => 0, 'last_checked_at' => now()]);

            if ($wasDown) {
                $this->notifyTenant(new MpesaGatewayRecovered());
            }

            return;
        }

        $count = $this->credentials->consecutive_failures + 1;
        $this->credentials->update(['consecutive_failures' => $count, 'last_checked_at' => now()]);

        if ($count === self::FAILURE_THRESHOLD) {
            $this->notifyTenant(new MpesaGatewayDown());
        }
    }

    private function notifyTenant($notification): void
    {
        $tenant = Tenant::find($this->credentials->tenant_id);
        if ($tenant) {
            Notification::send($tenant->users, $notification);
        }
    }
}

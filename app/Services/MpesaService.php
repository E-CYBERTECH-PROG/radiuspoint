<?php

namespace App\Services;

use App\Models\MpesaSetting;
use Illuminate\Support\Facades\Http;

class MpesaService
{
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

        return $response->json() ?? [];
    }
}

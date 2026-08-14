<?php

namespace App\Services;

use App\Models\SmsSetting;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Sends via Africa's Talking. Returns false and logs the reason rather than throwing,
     * so a failed alert SMS doesn't break the notification pipeline it's part of.
     */
    public function send(string $phone, string $message, Tenant $tenant): bool
    {
        $setting = SmsSetting::where('tenant_id', $tenant->id)->first();

        if (! $setting || ! $setting->username || ! $setting->api_key) {
            Log::info("SMS not sent to {$phone} — tenant #{$tenant->id} has no SMS provider credentials configured yet.");

            return false;
        }

        try {
            $response = Http::asForm()->withHeaders([
                'apiKey' => $setting->api_key,
                'Accept' => 'application/json',
            ])->post('https://api.africastalking.com/version1/messaging', [
                'username' => $setting->username,
                'to' => $phone,
                'message' => $message,
                'from' => $setting->sender_id,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning("SMS send failed for tenant #{$tenant->id}: " . $response->body());

            return false;
        } catch (\Throwable $e) {
            Log::warning("SMS send exception for tenant #{$tenant->id}: " . $e->getMessage());

            return false;
        }
    }
}

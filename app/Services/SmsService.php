<?php

namespace App\Services;

use App\Models\SmsSetting;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Sends via Africa's Talking — the dominant SMS gateway for Kenya-based ISPs, matching this
     * tenant's own market. Returns false (and logs why) rather than throwing: a failed alert-SMS
     * should never itself break the notification pipeline trying to warn someone about a
     * DIFFERENT problem. No tenant has entered real credentials yet, so in practice this
     * currently always hits the "not configured" branch — the moment a tenant fills in
     * Settings > SMS with a real Africa's Talking username + API key, this starts sending live.
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

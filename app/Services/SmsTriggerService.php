<?php

namespace App\Services;

use App\Models\SmsMessage;
use App\Models\SmsTrigger;

/**
 * Central dispatch point for every lifecycle SMS. A tenant maps a trigger key to one of their
 * own SmsTemplates (with {placeholder} variables) and enables it; call sites elsewhere in the
 * app just fire the event by key and don't need to know whether it's configured. Falling back to
 * a fixed message when nothing's configured means turning this on is opt-in — a tenant who never
 * touches the Automation tab sees no behavior change from what already existed at each call site.
 */
class SmsTriggerService
{
    public static function fire(int $tenantId, string $key, ?string $phone, array $vars = [], ?string $fallbackMessage = null): void
    {
        if (! $phone) {
            return;
        }

        $trigger = SmsTrigger::where('tenant_id', $tenantId)
            ->where('trigger_key', $key)
            ->where('enabled', true)
            ->with('template')
            ->first();

        $message = $trigger?->template
            ? strtr($trigger->template->body, self::placeholders($vars))
            : $fallbackMessage;

        if (! $message) {
            return;
        }

        SmsMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId,
            'phone_number' => $phone,
            'message' => $message,
            'status' => 'queued',
            'initiator' => 'Automation',
            'sms_template_id' => $trigger?->sms_template_id,
        ]);
    }

    private static function placeholders(array $vars): array
    {
        $map = [];
        foreach ($vars as $key => $value) {
            $map['{' . $key . '}'] = (string) ($value ?? '');
        }

        return $map;
    }
}

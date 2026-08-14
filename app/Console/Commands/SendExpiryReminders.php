<?php

namespace App\Console\Commands;

use App\Models\PppoeUser;
use App\Services\SmsTriggerService;
use Illuminate\Console\Command;

class SendExpiryReminders extends Command
{
    protected $signature = 'sms:expiry-reminders';

    protected $description = 'Reminds active PPPoE customers 3 days before their plan expires (only if the tenant has enabled the pppoe_expiry_reminder_3d trigger)';

    public function handle(): int
    {
        $sent = 0;

        $users = PppoeUser::withoutGlobalScope('tenant')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(3)])
            ->whereNull('expiry_reminder_sent_at')
            ->with('plan')
            ->get();

        foreach ($users as $user) {
            SmsTriggerService::fire($user->tenant_id, 'pppoe_expiry_reminder_3d', $user->phone_number, [
                'name' => $user->name ?: $user->username,
                'plan' => $user->plan?->name,
                'expires_at' => $user->expires_at->format('d M Y H:i'),
            ]);

            $user->update(['expiry_reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Processed {$sent} expiring PPPoE customer(s).");

        return self::SUCCESS;
    }
}

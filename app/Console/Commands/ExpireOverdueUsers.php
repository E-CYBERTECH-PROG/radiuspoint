<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Services\RadiusSyncService;
use Illuminate\Console\Command;

class ExpireOverdueUsers extends Command
{
    protected $signature = 'users:expire-overdue';

    protected $description = 'Flip active customers/vouchers past their expiry to expired and revoke RADIUS access';

    public function handle(): int
    {
        $expiredCount = 0;

        foreach (HotspotUser::withoutGlobalScope('tenant')->where('status', 'active')->where('expires_at', '<', now())->get() as $user) {
            $user->update(['status' => 'expired']);
            RadiusSyncService::remove($user->phone_number);
            $expiredCount++;
        }

        foreach (PppoeUser::withoutGlobalScope('tenant')->where('status', 'active')->where('expires_at', '<', now())->get() as $user) {
            $user->update(['status' => 'expired']);
            RadiusSyncService::remove($user->username);
            $expiredCount++;
        }

        $this->info("Expired {$expiredCount} overdue user(s).");

        return self::SUCCESS;
    }
}

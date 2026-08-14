<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Services\RadiusSyncService;
use Illuminate\Console\Command;

class ActivateUsedVouchers extends Command
{
    protected $signature = 'vouchers:activate';

    protected $description = 'Start the validity clock for any voucher that has just connected for the first time';

    public function handle(): int
    {
        $unusedVouchers = HotspotUser::withoutGlobalScope('tenant')->where('status', 'unused')->get();

        $activated = 0;

        foreach ($unusedVouchers as $voucher) {
            $firstConnect = RadiusSyncService::firstSessionStart($voucher->phone_number);

            if (! $firstConnect) {
                continue;
            }

            $plan = Plan::withoutGlobalScope('tenant')->find($voucher->current_plan_id);

            if (! $plan) {
                continue;
            }

            $expiresAt = $plan->addDurationTo($firstConnect);

            $voucher->update([
                'status' => 'active',
                'expires_at' => $expiresAt,
                // A voucher code has no router context until redeemed — pick it up now from
                // whichever router actually authenticated the first session, unless an admin
                // already assigned one manually.
                'current_router_id' => $voucher->current_router_id ?? RadiusSyncService::firstSessionRouterId($voucher->phone_number),
                // Same story for the device MAC (RADIUS Calling-Station-Id) — FreeRADIUS has
                // always captured this into radacct, nothing previously read it back onto the
                // customer record.
                'mac_address' => $voucher->mac_address ?? RadiusSyncService::latestSessionMac($voucher->phone_number),
            ]);

            RadiusSyncService::setExpiration($voucher->phone_number, $expiresAt);

            $activated++;
        }

        $this->info("Activated {$activated} voucher(s) on first use.");

        return self::SUCCESS;
    }
}

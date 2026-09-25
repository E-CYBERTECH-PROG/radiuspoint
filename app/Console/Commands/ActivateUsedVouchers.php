<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Services\ExpiredBlockService;
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

            // Always computed from the real first-connect time, but never allowed to land in
            // the past by the time this actually runs — this command is polled once a minute,
            // and users:expire-overdue right behind it, so a short-duration voucher (or a
            // scheduler that's fallen behind) could otherwise have its entire validity window
            // eaten by that lag and be yanked on the very next tick, before the customer who
            // just redeemed it ever got to use it. A floor of 5 minutes from actual activation
            // guarantees every voucher gets at least some real usable time.
            $expiresAt = $plan->addDurationTo($firstConnect)->max(now()->addMinutes(5));
            $routerId = $voucher->current_router_id ?? RadiusSyncService::firstSessionRouterId($voucher->phone_number);

            $voucher->update([
                'status' => 'active',
                'expires_at' => $expiresAt,
                // Router of the first session, unless already assigned manually.
                'current_router_id' => $routerId,
                // Device MAC from the RADIUS Calling-Station-Id, via radacct.
                'mac_address' => $voucher->mac_address ?? RadiusSyncService::latestSessionMac($voucher->phone_number),
            ]);

            RadiusSyncService::setExpiryWindow($voucher->phone_number, $expiresAt);

            // Same device, same DHCP pool — a second voucher very often gets handed back the
            // exact IP its previous (now-expired) voucher held. If that IP is still sitting on
            // the router's `{slug}-expired` block list from ExpireOverdueUsers::blockIfConnected(),
            // this fresh voucher's traffic would be silently dropped despite a valid RADIUS
            // login: "connected, no internet". Same fix already applied to PPPoE renewals
            // (PppoeUserController::extendExpiry()) and M-Pesa hotspot purchases
            // (BillingController) — vouchers just never had an activation hook to hang it on
            // until now.
            if ($routerId) {
                ExpiredBlockService::clear(Router::withoutGlobalScope('tenant')->find($routerId), $voucher->phone_number);
            }

            $activated++;
        }

        $this->info("Activated {$activated} voucher(s) on first use.");

        return self::SUCCESS;
    }
}

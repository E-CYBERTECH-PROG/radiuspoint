<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Services\RadiusSyncService;
use Illuminate\Console\Command;

/**
 * Backfills mac_address/current_router_id for customers who are already 'active' but never got
 * either set — the self-service M-Pesa flow (BillingController::activateHotspotUser()) creates
 * the customer as 'active' *before* they've ever connected, so at creation time there's no
 * radacct session yet to read a MAC or router from. This is the only point that gap ever gets
 * closed, once they've actually authenticated for the first time. ActivateUsedVouchers covers the
 * same two fields but only at the unused->active transition; this covers every other active
 * customer regardless of how they got activated.
 */
class SyncHotspotConnectionInfo extends Command
{
    protected $signature = 'hotspot:sync-connection-info';

    protected $description = 'Backfills MAC address and router link for active hotspot customers who connected after being activated';

    public function handle(): int
    {
        $candidates = HotspotUser::withoutGlobalScope('tenant')
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('mac_address')->orWhereNull('current_router_id'))
            ->get();

        $updated = 0;

        foreach ($candidates as $user) {
            $changes = [];

            if (! $user->mac_address) {
                $mac = RadiusSyncService::latestSessionMac($user->phone_number);
                if ($mac) {
                    $changes['mac_address'] = $mac;
                }
            }

            if (! $user->current_router_id) {
                $routerId = RadiusSyncService::latestSessionRouterId($user->phone_number);
                if ($routerId) {
                    $changes['current_router_id'] = $routerId;
                }
            }

            if ($changes) {
                $user->update($changes);
                $updated++;
            }
        }

        $this->info("Backfilled connection info for {$updated} customer(s).");

        return self::SUCCESS;
    }
}

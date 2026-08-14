<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Services\MikrotikApiService;
use App\Services\RadiusSyncService;
use App\Services\SessionDisconnectService;
use App\Services\SmsTriggerService;
use Illuminate\Console\Command;
use Throwable;

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

            if ($user->router) {
                $this->blockIfConnected($user->router, '/ip/hotspot/active/print', 'user', $user->phone_number);
                SessionDisconnectService::disconnect($user->router, '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove', $user->phone_number);
            }

            $expiredCount++;
        }

        foreach (PppoeUser::withoutGlobalScope('tenant')->where('status', 'active')->where('expires_at', '<', now())->with('plan')->get() as $user) {
            $user->update(['status' => 'expired']);
            RadiusSyncService::remove($user->username);

            if ($user->router) {
                $this->blockIfConnected($user->router, '/ppp/active/print', 'name', $user->username);
                SessionDisconnectService::disconnect($user->router, '/ppp/active/print', 'name', '/ppp/active/remove', $user->username);
            }

            SmsTriggerService::fire($user->tenant_id, 'pppoe_expired', $user->phone_number, [
                'name' => $user->name ?: $user->username,
                'plan' => $user->plan?->name,
            ]);

            $expiredCount++;
        }

        $this->info("Expired {$expiredCount} overdue user(s).");

        return self::SUCCESS;
    }

    /**
     * Deleting the RADIUS credential only stops the customer's *next* login attempt — RouterOS
     * never re-checks RADIUS for a session that's already authenticated (no CoA here), so a
     * customer who was mid-session at the exact moment they expired keeps full internet access
     * until they happen to disconnect on their own. Adding their session's current IP to the
     * `radiuspoint-expired` address-list makes the `radiuspoint-expired-block` firewall rule (see
     * RouterController::enableRadiusOnDefaultProfiles()) cut their traffic immediately —
     * independent of whether the disconnect call below actually succeeds, rather than relying
     * solely on tearing the session down.
     */
    private function blockIfConnected(Router $router, string $activeEndpoint, string $activeUserField, string $username): void
    {
        try {
            $api = new MikrotikApiService();
            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return;
            }

            $session = collect($api->query($activeEndpoint))->first(fn ($row) => $row[$activeUserField] === $username);
            $address = $session['address'] ?? null;

            if (! $address) {
                return;
            }

            $existing = $api->findId('/ip/firewall/address-list/print', 'address', $address);
            if (! $existing) {
                $api->query('/ip/firewall/address-list/add', [
                    'list' => 'radiuspoint-expired',
                    'address' => $address,
                    'timeout' => '1d',
                    'comment' => "expired: {$username}",
                ]);
            }
        } catch (Throwable $e) {
            // Best-effort — the RADIUS credential removal above still stops their next login
            // even if this block (or the disconnect call after it) can't reach the router.
        }
    }
}

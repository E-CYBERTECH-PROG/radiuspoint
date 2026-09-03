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

            // An auto-purchased account can have two valid RADIUS credentials at once (see
            // HotspotUser::radiusUsernames()) — both must be revoked, and the customer could
            // be actively connected under either one.
            foreach ($user->radiusUsernames() as $radiusUsername) {
                RadiusSyncService::remove($radiusUsername);

                if ($user->router) {
                    $this->blockIfConnected($user->router, '/ip/hotspot/active/print', 'user', $radiusUsername);
                    SessionDisconnectService::disconnect($user->router, '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove', $radiusUsername);
                }
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
     * Removing the RADIUS credential only blocks the next login attempt, not an already-active
     * session (no CoA support). This adds the session's IP to the router's tenant-branded
     * `{slug}-expired` address-list so the firewall rule in
     * RouterController::enableRadiusOnDefaultProfiles() cuts traffic immediately, regardless
     * of whether the disconnect call below succeeds.
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
                    'list' => $router->namingSlug().'-expired',
                    'address' => $address,
                    'timeout' => '1d',
                    'comment' => "expired: {$username}",
                ]);
            }
        } catch (Throwable $e) {
            // Best-effort; RADIUS removal above still blocks their next login.
        }
    }
}

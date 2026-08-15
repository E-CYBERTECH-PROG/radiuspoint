<?php

namespace App\Services;

use App\Models\Router;
use Throwable;

/**
 * Reverses ExpireOverdueUsers::blockIfConnected() — that firewall address-list entry
 * drops all traffic from the customer's IP and is only self-removed by RouterOS after
 * its 1-day timeout. Without this, a customer who buys again on the same day a plan
 * lapsed can come back online (RADIUS/hotspot login succeeds) while the leftover block
 * still silently drops their traffic, showing as "connected, no internet".
 */
class ExpiredBlockService
{
    public static function clear(?Router $router, string $username): void
    {
        if (! $router) {
            return;
        }

        try {
            $api = new MikrotikApiService();
            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return;
            }

            foreach ($api->queryWhere('/ip/firewall/address-list/print', 'comment', "expired: {$username}") as $entry) {
                $api->query('/ip/firewall/address-list/remove', ['.id' => $entry['.id']]);
            }
        } catch (Throwable $e) {
            // Best-effort — the entry still self-clears after its 1-day timeout.
        }
    }
}

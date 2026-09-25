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
            $api = app(MikrotikApiService::class);
            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return;
            }

            // Matching by comment alone isn't enough: the DHCP pool recycles IPs across
            // completely different usernames, so the block sitting on the address this
            // customer's device just got handed is very often still tagged with somebody
            // else's name from whenever *they* expired — confirmed live: voucher "790" came
            // online on 10.100.0.252 while that exact address was still blocked under
            // "expired: 90" from an unrelated customer hours earlier. A leftover block is
            // wrong the moment ANY currently-authenticated session holds that address,
            // regardless of whose name is on it — so first clear by this customer's actual
            // current IP (from their live session, if they have one yet), then also clear by
            // their own username as a fallback for an extend/activation that happens before
            // they've reconnected (no active session yet to read an IP from).
            $address = self::currentAddressFor($api, $username);

            if ($address) {
                foreach ($api->queryWhere('/ip/firewall/address-list/print', 'address', $address) as $entry) {
                    $api->query('/ip/firewall/address-list/remove', ['.id' => $entry['.id']]);
                }
            }

            foreach ($api->queryWhere('/ip/firewall/address-list/print', 'comment', "expired: {$username}") as $entry) {
                $api->query('/ip/firewall/address-list/remove', ['.id' => $entry['.id']]);
            }
        } catch (Throwable $e) {
            // Best-effort — any entry left behind still self-clears after its 1-day timeout.
        }
    }

    /**
     * This username's IP right now, checking both hotspot and PPPoE active-session tables
     * since callers don't otherwise say which kind of customer this is.
     */
    private static function currentAddressFor(MikrotikApiService $api, string $username): ?string
    {
        foreach ([['/ip/hotspot/active/print', 'user'], ['/ppp/active/print', 'name']] as [$endpoint, $field]) {
            $session = collect($api->query($endpoint))->first(fn ($row) => ($row[$field] ?? null) === $username);

            if ($session && ! empty($session['address'])) {
                return $session['address'];
            }
        }

        return null;
    }
}

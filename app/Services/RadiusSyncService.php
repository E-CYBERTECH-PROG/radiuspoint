<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RadiusSyncService
{
    public static function sync(string $username, string $password, ?string $speedLimit): void
    {
        DB::table('radcheck')->updateOrInsert(
            ['username' => $username, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $password, 'updated_at' => now(), 'created_at' => now()]
        );

        if ($speedLimit) {
            DB::table('radreply')->updateOrInsert(
                ['username' => $username, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => '=', 'value' => $speedLimit, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public static function remove(string $username): void
    {
        DB::table('radcheck')->where('username', $username)->delete();
        DB::table('radreply')->where('username', $username)->delete();
    }

    public static function hasCredential(string $username): bool
    {
        return DB::table('radcheck')->where('username', $username)->exists();
    }

    public static function updateRateLimit(string $username, ?string $speedLimit): void
    {
        if ($speedLimit) {
            DB::table('radreply')->updateOrInsert(
                ['username' => $username, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => '=', 'value' => $speedLimit, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    /**
     * Set the FreeRADIUS "Expiration" check-attribute so the server itself rejects
     * authentication once the wall-clock deadline passes — enforced regardless of
     * connect/disconnect state, without needing an active session to "count down."
     *
     * Format matches the common rlm_expiration/SQL-schema convention ("d M Y H:i:s").
     * Verify this matches your live FreeRADIUS server's configured date format if
     * expiration doesn't take effect — some installs expect date-only ("d M Y").
     */
    public static function setExpiration(string $username, Carbon $expiresAt): void
    {
        DB::table('radcheck')->updateOrInsert(
            ['username' => $username, 'attribute' => 'Expiration'],
            ['op' => ':=', 'value' => $expiresAt->format('d M Y H:i:s'), 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public static function clearExpiration(string $username): void
    {
        DB::table('radcheck')->where('username', $username)->where('attribute', 'Expiration')->delete();
    }

    /**
     * Standard RADIUS Session-Timeout (not Mikrotik-vendor-specific) — tells the NAS to end
     * the session itself after N seconds, no cron/disconnect call needed. Used by Free Mode to
     * cap a session at a fixed window without any extra enforcement machinery.
     */
    public static function setSessionTimeout(string $username, int $seconds): void
    {
        DB::table('radreply')->updateOrInsert(
            ['username' => $username, 'attribute' => 'Session-Timeout'],
            ['op' => '=', 'value' => (string) $seconds, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Mikrotik-Group reply attribute — ties a RADIUS-authenticated session (which has no local
     * account on the router) to a *local* `/ip/hotspot/user/profile` by name, so it inherits
     * that profile's `address-list` tagging. Used by Free Mode alongside Mikrotik-Rate-Limit
     * (belt-and-braces: the rate cap is the confirmed-working restriction; the group/address-list
     * domain filter is the best-effort secondary layer — verify against a live device if the
     * domain restriction doesn't seem to apply).
     */
    public static function setGroup(string $username, string $group): void
    {
        DB::table('radreply')->updateOrInsert(
            ['username' => $username, 'attribute' => 'Mikrotik-Group'],
            ['op' => '=', 'value' => $group, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Which router (by our own Router id, matched via nasipaddress) this username's first-ever
     * RADIUS session actually came through, or null if they've never connected. Lets a voucher
     * pick up its router link on activation the same way a self-service M-Pesa purchase does via
     * Transaction::router_id — vouchers have no router context at creation time (a printed code
     * is generic until redeemed), so this is the only point it can be captured.
     */
    public static function firstSessionRouterId(string $username): ?int
    {
        $nasIp = DB::table('radacct')
            ->where('username', $username)
            ->whereNotNull('acctstarttime')
            ->orderBy('acctstarttime')
            ->value('nasipaddress');

        if (! $nasIp) {
            return null;
        }

        return \App\Models\Router::withoutGlobalScope('tenant')->where('ip_address', $nasIp)->value('id');
    }

    /**
     * Timestamp of this username's first-ever RADIUS accounting session (i.e. first real
     * connect), or null if they've never successfully authenticated yet.
     */
    public static function firstSessionStart(string $username): ?Carbon
    {
        $timestamp = DB::table('radacct')
            ->where('username', $username)
            ->whereNotNull('acctstarttime')
            ->orderBy('acctstarttime')
            ->value('acctstarttime');

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * The device MAC address (RADIUS Calling-Station-Id, standard radacct column
     * `callingstationid`) from this username's most recent session — RouterOS/FreeRADIUS have
     * been capturing this into radacct the whole time; nothing in the app ever read it back onto
     * HotspotUser.mac_address. Most-recent (not first) since a customer's actual device can
     * change between sessions and the latest one is the one worth showing/binding.
     */
    public static function latestSessionMac(string $username): ?string
    {
        return DB::table('radacct')
            ->where('username', $username)
            ->whereNotNull('callingstationid')
            ->orderByDesc('acctstarttime')
            ->value('callingstationid');
    }

    /**
     * Same idea as firstSessionRouterId() but from the most recent session — used to backfill an
     * already-active customer's current_router_id after the fact, not just at voucher-activation
     * time.
     */
    public static function latestSessionRouterId(string $username): ?int
    {
        $nasIp = DB::table('radacct')
            ->where('username', $username)
            ->whereNotNull('acctstarttime')
            ->orderByDesc('acctstarttime')
            ->value('nasipaddress');

        if (! $nasIp) {
            return null;
        }

        return \App\Models\Router::withoutGlobalScope('tenant')->where('ip_address', $nasIp)->value('id');
    }
}

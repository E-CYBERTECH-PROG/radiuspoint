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
     * Sets the FreeRADIUS "Expiration" check-attribute so the server rejects
     * authentication once the deadline passes. Date format follows the standard
     * rlm_expiration convention ("d M Y H:i:s").
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
     * Sets both the calendar-date reject (Expiration) and the accurate per-session countdown
     * (Session-Timeout) from the same deadline, so they can never disagree. This is the one
     * that should be called everywhere a customer's expiry is established or changed — creation,
     * enable, extend, voucher activation — not setExpiration() alone. Without Session-Timeout,
     * RouterOS's hotspot has nothing to compute $(session-time-left) from (it only reads that
     * off the RADIUS reply, never the check-only Expiration attribute), so it shows blank/stale
     * — and since Session-Timeout is only ever a snapshot taken at sync time, not something
     * FreeRADIUS recomputes live per request, this is "accurate as of the last time we touched
     * this account" rather than perfectly live between those events; Expiration is what actually
     * guarantees a customer can never authenticate past their real deadline regardless.
     */
    public static function setExpiryWindow(string $username, Carbon $expiresAt): void
    {
        self::setExpiration($username, $expiresAt);

        $secondsRemaining = max(60, $expiresAt->getTimestamp() - now()->getTimestamp());
        self::setSessionTimeout($username, $secondsRemaining);
    }

    /**
     * Standard RADIUS Session-Timeout attribute — tells the NAS to end the session
     * itself after N seconds. Used by Free Mode to cap a session at a fixed window.
     */
    public static function setSessionTimeout(string $username, int $seconds): void
    {
        DB::table('radreply')->updateOrInsert(
            ['username' => $username, 'attribute' => 'Session-Timeout'],
            ['op' => '=', 'value' => (string) $seconds, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Mikrotik-Group reply attribute — ties a RADIUS-authenticated session to a local
     * hotspot user profile by name, so it inherits that profile's address-list tagging.
     * Used by Free Mode alongside Mikrotik-Rate-Limit as a secondary domain-filter layer.
     */
    public static function setGroup(string $username, string $group): void
    {
        DB::table('radreply')->updateOrInsert(
            ['username' => $username, 'attribute' => 'Mikrotik-Group'],
            ['op' => '=', 'value' => $group, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * The Router this username's first-ever RADIUS session came through (matched via
     * nasipaddress), or null if they've never connected. Lets a voucher pick up its
     * router link on activation.
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
     * The device MAC address (radacct's callingstationid) from this username's most
     * recent session — most recent since a customer's device can change between sessions.
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
     * Same as firstSessionRouterId() but from the most recent session — used to backfill
     * an already-active customer's current_router_id.
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

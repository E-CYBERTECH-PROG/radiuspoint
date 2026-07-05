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
}

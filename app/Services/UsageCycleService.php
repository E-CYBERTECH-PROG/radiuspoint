<?php

namespace App\Services;

use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "How much has this customer used since their current billing cycle started?" — the cycle has
 * no dedicated start-date column; it's derived by walking the plan's duration back from
 * expires_at, matching how App\Console\Commands\EnforceFairUsage already computed this inline.
 * Pulled out here so the admin-facing "Data Usage" display (HotspotUserController /
 * PppoeUserController) and the FUP enforcement job share one definition instead of two that
 * could quietly drift apart.
 */
class UsageCycleService
{
    public static function cycleStart(Plan $plan, ?Carbon $expiresAt): Carbon
    {
        $expiresAt ??= now();
        $value = $plan->duration_value ?: 1;
        $start = $expiresAt->copy();

        return match ($plan->duration_unit) {
            'minutes' => $start->subMinutes($value),
            'hours' => $start->subHours($value),
            'weeks' => $start->subWeeks($value),
            'months' => $start->subMonths($value),
            default => $start->subDays($value),
        };
    }

    /**
     * Bytes used (input + output combined) since the cycle started, summed straight from
     * radacct — the same source of truth EnforceFairUsage checks against the plan's data_cap_mb.
     *
     * FreeRADIUS writes acctstarttime using the DB server's own raw clock (confirmed UTC on this
     * box), independent of app.timezone/the per-tenant timezone a web request may be running
     * under (see ApplyTenantTimezone). $cycleStart is built from app-side Carbon calls and can
     * therefore be in Africa/Nairobi (or whatever the tenant picked) — normalize to UTC before
     * comparing, or this silently excludes/includes hours of real usage depending on the offset.
     */
    public static function bytesUsed(string $username, Carbon $cycleStart): int
    {
        return (int) DB::table('radacct')
            ->where('username', $username)
            ->where('acctstarttime', '>=', $cycleStart->clone()->setTimezone('UTC'))
            ->selectRaw('COALESCE(SUM(acctinputoctets + acctoutputoctets), 0) as total')
            ->value('total');
    }
}

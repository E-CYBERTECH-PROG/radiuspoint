<?php

namespace App\Services;

use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes how much a customer has used since their current billing cycle started.
 * There's no dedicated start-date column, so the cycle start is derived by walking
 * the plan's duration back from expires_at.
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
     * Bytes used (input + output combined) since the cycle started, summed from radacct.
     * Accepts more than one username since an auto-purchased hotspot account can have two
     * valid RADIUS credentials at once (see HotspotUser::radiusUsernames()) — usage under
     * either one counts toward the same account's cap.
     *
     * FreeRADIUS writes acctstarttime in UTC regardless of the app/tenant timezone, so
     * $cycleStart must be normalized to UTC before comparing.
     */
    public static function bytesUsed(string|array $username, Carbon $cycleStart): int
    {
        return (int) DB::table('radacct')
            ->when(
                is_array($username),
                fn ($q) => $q->whereIn('username', $username),
                fn ($q) => $q->where('username', $username)
            )
            ->where('acctstarttime', '>=', $cycleStart->clone()->setTimezone('UTC'))
            ->selectRaw('COALESCE(SUM(acctinputoctets + acctoutputoctets), 0) as total')
            ->value('total');
    }
}

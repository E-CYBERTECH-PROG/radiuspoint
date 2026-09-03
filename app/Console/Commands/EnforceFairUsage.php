<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Services\RadiusSyncService;
use App\Services\SessionDisconnectService;
use App\Services\UsageCycleService;
use Illuminate\Console\Command;

class EnforceFairUsage extends Command
{
    protected $signature = 'fup:enforce';

    protected $description = 'Throttles customers who\'ve exceeded their plan\'s data cap to its fup_speed_limit, and restores full speed once a new billing cycle starts';

    public function handle(): int
    {
        $this->enforce(HotspotUser::class, '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove');
        $this->enforce(PppoeUser::class, '/ppp/active/print', 'name', '/ppp/active/remove');

        return self::SUCCESS;
    }

    /**
     * @param  class-string<HotspotUser|PppoeUser>  $modelClass  both implement radiusUsername()
     * @param  string  $activeEndpoint  RouterOS endpoint listing active sessions
     * @param  string  $activeUserField  which field on an active-session row holds the username
     * @param  string  $removeEndpoint  RouterOS endpoint to end a specific active session
     */
    private function enforce(string $modelClass, string $activeEndpoint, string $activeUserField, string $removeEndpoint): void
    {
        $users = $modelClass::withoutGlobalScope('tenant')
            ->where('status', 'active')
            ->whereNotNull('current_router_id')
            ->whereNotNull('current_plan_id')
            ->with(['plan', 'router'])
            ->get()
            ->filter(fn ($user) => $user->plan && $user->plan->data_cap_mb && $user->plan->fup_speed_limit);

        foreach ($users as $user) {
            $radiusUsername = $user->radiusUsername();
            $cycleStart = UsageCycleService::cycleStart($user->plan, $user->expires_at);

            // New cycle since last throttle — restore full speed before re-checking usage.
            if ($user->fup_throttled_at && $cycleStart->gt($user->fup_throttled_at)) {
                RadiusSyncService::updateRateLimit($radiusUsername, $user->plan->speed_limit);
                $user->update(['fup_throttled_at' => null]);
            }

            if ($user->fup_throttled_at) {
                continue;
            }

            $usedBytes = UsageCycleService::bytesUsed($radiusUsername, $cycleStart);

            if ($usedBytes < $user->plan->data_cap_mb * 1024 * 1024) {
                continue;
            }

            RadiusSyncService::updateRateLimit($radiusUsername, $user->plan->fup_speed_limit);
            // radreply only applies on next re-auth (no CoA support), so force a disconnect
            // to make the throttle take effect immediately. Best-effort either way.
            SessionDisconnectService::disconnect($user->router, $activeEndpoint, $activeUserField, $removeEndpoint, $radiusUsername);
            $user->update(['fup_throttled_at' => now()]);
        }
    }
}

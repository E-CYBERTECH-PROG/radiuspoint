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
        $this->enforce(HotspotUser::class, 'phone_number', '/ip/hotspot/active/print', 'user', '/ip/hotspot/active/remove');
        $this->enforce(PppoeUser::class, 'username', '/ppp/active/print', 'name', '/ppp/active/remove');

        return self::SUCCESS;
    }

    /**
     * @param  class-string<HotspotUser|PppoeUser>  $modelClass
     * @param  string  $usernameColumn  the RADIUS-username-bearing column on this model
     * @param  string  $activeEndpoint  RouterOS endpoint listing active sessions
     * @param  string  $activeUserField  which field on an active-session row holds the username
     * @param  string  $removeEndpoint  RouterOS endpoint to end a specific active session
     */
    private function enforce(string $modelClass, string $usernameColumn, string $activeEndpoint, string $activeUserField, string $removeEndpoint): void
    {
        $users = $modelClass::withoutGlobalScope('tenant')
            ->where('status', 'active')
            ->whereNotNull('current_router_id')
            ->whereNotNull('current_plan_id')
            ->with(['plan', 'router'])
            ->get()
            ->filter(fn ($user) => $user->plan && $user->plan->data_cap_mb && $user->plan->fup_speed_limit);

        foreach ($users as $user) {
            $cycleStart = UsageCycleService::cycleStart($user->plan, $user->expires_at);

            // A new cycle started since the customer was last throttled — restore full speed
            // before checking usage against the new cycle's cap.
            if ($user->fup_throttled_at && $cycleStart->gt($user->fup_throttled_at)) {
                RadiusSyncService::updateRateLimit($user->{$usernameColumn}, $user->plan->speed_limit);
                $user->update(['fup_throttled_at' => null]);
            }

            if ($user->fup_throttled_at) {
                continue; // already throttled for the current cycle — nothing more to do
            }

            $usedBytes = UsageCycleService::bytesUsed($user->{$usernameColumn}, $cycleStart);

            if ($usedBytes < $user->plan->data_cap_mb * 1024 * 1024) {
                continue;
            }

            RadiusSyncService::updateRateLimit($user->{$usernameColumn}, $user->plan->fup_speed_limit);
            // radreply only takes effect on a customer's NEXT re-authentication (confirmed — no
            // CoA capability exists in this app), so the throttle wouldn't bite until they
            // happened to reconnect on their own. Forcing one disconnect now makes it immediate.
            // Best-effort: radreply is already updated either way, so the throttle takes effect
            // on their next reconnect regardless of whether this succeeds.
            SessionDisconnectService::disconnect($user->router, $activeEndpoint, $activeUserField, $removeEndpoint, $user->{$usernameColumn});
            $user->update(['fup_throttled_at' => now()]);
        }
    }
}

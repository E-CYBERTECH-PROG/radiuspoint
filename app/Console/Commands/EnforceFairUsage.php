<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Services\MikrotikApiService;
use App\Services\RadiusSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

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
            $cycleStart = $this->cycleStart($user->plan, $user->expires_at);

            // A new cycle started since the customer was last throttled — restore full speed
            // before checking usage against the new cycle's cap.
            if ($user->fup_throttled_at && $cycleStart->gt($user->fup_throttled_at)) {
                RadiusSyncService::updateRateLimit($user->{$usernameColumn}, $user->plan->speed_limit);
                $user->update(['fup_throttled_at' => null]);
            }

            if ($user->fup_throttled_at) {
                continue; // already throttled for the current cycle — nothing more to do
            }

            $usedBytes = DB::table('radacct')
                ->where('username', $user->{$usernameColumn})
                ->where('acctstarttime', '>=', $cycleStart)
                ->selectRaw('COALESCE(SUM(acctinputoctets + acctoutputoctets), 0) as total')
                ->value('total');

            if ((int) $usedBytes < $user->plan->data_cap_mb * 1024 * 1024) {
                continue;
            }

            RadiusSyncService::updateRateLimit($user->{$usernameColumn}, $user->plan->fup_speed_limit);
            $this->forceReconnect($user->router, $user->{$usernameColumn}, $activeEndpoint, $activeUserField, $removeEndpoint);
            $user->update(['fup_throttled_at' => now()]);
        }
    }

    /**
     * radreply only takes effect on a customer's NEXT re-authentication (confirmed — no CoA
     * capability exists in this app), so the throttle wouldn't bite until they happened to
     * reconnect on their own. Forcing one disconnect now makes it immediate, reusing the exact
     * mechanism already proven in RouterController::disconnectHotspotUser/disconnectPppoeUser.
     */
    private function forceReconnect($router, string $username, string $activeEndpoint, string $activeUserField, string $removeEndpoint): void
    {
        try {
            $api = new MikrotikApiService();
            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return;
            }

            $session = collect($api->query($activeEndpoint))->first(fn ($row) => $row[$activeUserField] === $username);
            if ($session) {
                $api->query($removeEndpoint, ['.id' => $session['.id']]);
            }
        } catch (Throwable $e) {
            // Best-effort — radreply is already updated, so the throttle takes effect on their
            // next reconnect regardless of whether we managed to force one now.
        }
    }

    private function cycleStart($plan, ?Carbon $expiresAt): Carbon
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
}

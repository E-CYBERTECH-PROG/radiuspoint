<?php

namespace App\Console\Commands;

use App\Http\Controllers\RouterController;
use App\Models\Router;
use App\Notifications\RouterCriticalLog;
use App\Notifications\RouterOffline;
use App\Notifications\RouterOnline;
use App\Services\MikrotikApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RouterHealthCheck extends Command
{
    protected $signature = 'router:health-check';

    protected $description = 'Polls every provisioned router\'s live status (feeding the NOC overview page\'s DB snapshot), fires online/offline alerts on transitions, and scans for new critical log entries';

    public function handle(): int
    {
        // Skip 'pending' routers — they haven't finished the provisioning wizard yet.
        $routers = Router::withoutGlobalScope('tenant')
            ->whereNotIn('status', ['pending'])
            ->with('tenant.users')
            ->get();

        foreach ($routers as $router) {
            $previousStatus = $router->status;
            $api = new MikrotikApiService();
            $reachable = $api->connect($router->ip_address, $router->api_username, $router->api_password);

            if ($reachable) {
                try {
                    $api->query('/system/resource/print');
                    $router->update(['status' => 'active', 'last_seen' => now()]);
                    $this->scanCriticalLogs($api, $router);
                    // Reuses this connection to push RADIUS profile settings to every router;
                    // the only path that reaches PPPoE-only routers. Idempotent and cheap.
                    (new RouterController())->enableRadiusOnDefaultProfiles($api);
                } catch (Throwable $e) {
                    $reachable = false;
                    $router->update(['status' => 'offline']);
                }
            } else {
                $router->update(['status' => 'offline']);
            }

            $this->notifyTransition($router, $previousStatus, $reachable ? 'active' : 'offline');
        }

        return self::SUCCESS;
    }

    /**
     * Only fires for active<->offline flips. Excludes 'provisioning' so first contact during
     * setup doesn't trigger a "back online" notification.
     */
    private function notifyTransition(Router $router, string $previousStatus, string $newStatus): void
    {
        if ($previousStatus === $newStatus || ! in_array($previousStatus, ['active', 'offline'], true)) {
            return;
        }

        $users = $router->tenant->users;

        if ($newStatus === 'offline') {
            Notification::send($users, new RouterOffline($router));
        } else {
            Notification::send($users, new RouterOnline($router));
        }
    }

    /**
     * RouterOS log entries use a plain "Y-m-d H:i:s" `time` field. Dedup compares timestamps
     * rather than log .id, since .id isn't stable once the buffer rotates.
     */
    private function scanCriticalLogs(MikrotikApiService $api, Router $router): void
    {
        try {
            $logs = $api->query('/log/print');
        } catch (Throwable $e) {
            return;
        }

        $newest = $router->last_log_alert_at;

        foreach ($logs as $log) {
            $topics = $log['topics'] ?? '';
            if (! str_contains($topics, 'critical') && ! str_contains($topics, 'error')) {
                continue;
            }

            $time = isset($log['time']) ? Carbon::parse($log['time']) : null;
            if (! $time || ($router->last_log_alert_at && $time->lte($router->last_log_alert_at))) {
                continue;
            }

            Notification::send($router->tenant->users, new RouterCriticalLog($router, $log['message'] ?? 'Unknown'));

            if (! $newest || $time->gt($newest)) {
                $newest = $time;
            }
        }

        if ($newest && $newest !== $router->last_log_alert_at) {
            $router->update(['last_log_alert_at' => $newest]);
        }
    }
}

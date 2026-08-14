<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\PlanRouterSync;
use App\Models\Router;
use App\Services\MikrotikApiService;
use Illuminate\Console\Command;
use Throwable;

class PlanReconcile extends Command
{
    protected $signature = 'plan:reconcile';

    protected $description = 'Pushes every Plan to every active router\'s MikroTik profile config, idempotently — this is the only place Plan-to-router sync happens now that saving a Plan no longer pushes synchronously';

    public function handle(): int
    {
        $routers = Router::withoutGlobalScope('tenant')->where('status', 'active')->get();

        foreach ($routers as $router) {
            // A plan with no router_ids applies to every router; otherwise only to those listed.
            $plans = Plan::withoutGlobalScope('tenant')
                ->where('tenant_id', $router->tenant_id)
                ->where(function ($query) use ($router) {
                    $query->whereDoesntHave('routers')
                        ->orWhereHas('routers', fn ($q) => $q->where('routers.id', $router->id));
                })
                ->get();

            if ($plans->isEmpty()) {
                continue;
            }

            $api = new MikrotikApiService();

            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                foreach ($plans as $plan) {
                    $this->recordResult($plan, $router, 'failed', 'Router unreachable.');
                }
                continue;
            }

            foreach ($plans as $plan) {
                $this->syncPlanToRouter($api, $plan, $router);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Checks for an existing profile before adding one, so this is safe to run repeatedly
     * against the same router/plan pair without RouterOS rejecting a duplicate name.
     */
    private function syncPlanToRouter(MikrotikApiService $api, Plan $plan, Router $router): void
    {
        $endpoint = $plan->type === 'hotspot' ? '/ip/hotspot/user/profile' : '/ppp/profile';

        try {
            $id = $api->findId("{$endpoint}/print", 'name', $plan->name);

            if (! $id) {
                $attrs = ['name' => $plan->name, 'rate-limit' => $plan->speed_limit];
                if ($plan->type === 'hotspot') {
                    $attrs['shared-users'] = '1';
                    $attrs['transparent-proxy'] = 'yes';
                } else {
                    $attrs['only-one'] = 'yes';
                }
                $api->query("{$endpoint}/add", $attrs);
                $this->recordResult($plan, $router, 'synced', 'Created.');

                return;
            }

            // Only /set if the rate limit drifted, to avoid needless writes to live hardware.
            // Matched via a closure, not firstWhere('.id', ...): firstWhere() treats the leading
            // dot in RouterOS's ".id" field as dot-notation and never matches.
            $current = collect($api->query("{$endpoint}/print"))->first(fn ($row) => $row['.id'] === $id);
            if (($current['rate-limit'] ?? null) !== $plan->speed_limit) {
                $api->setById("{$endpoint}/set", $id, ['rate-limit' => $plan->speed_limit]);
                $this->recordResult($plan, $router, 'synced', 'Updated rate limit.');
            } else {
                $this->recordResult($plan, $router, 'synced', 'Already up to date.');
            }
        } catch (Throwable $e) {
            $this->recordResult($plan, $router, 'failed', $e->getMessage());
        }
    }

    private function recordResult(Plan $plan, Router $router, string $status, string $message): void
    {
        PlanRouterSync::updateOrCreate(
            ['plan_id' => $plan->id, 'router_id' => $router->id],
            ['status' => $status, 'message' => $message, 'synced_at' => now()]
        );
    }
}

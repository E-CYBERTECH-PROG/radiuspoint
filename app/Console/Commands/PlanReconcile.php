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
            // A plan with no router_ids at all applies everywhere (the pre-existing default for
            // every plan created before this restriction existed); a plan WITH rows only applies
            // to the routers explicitly listed.
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
     * Check-before-act, not an unconditional /add — the old synchronous syncToRouters() always
     * called /add and would throw an uncaught RuntimeException the moment RouterOS rejected a
     * duplicate profile name on a second sync. findId() first means this is safe to run every
     * minute against the same router/plan pair indefinitely.
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

            // Only issue a /set if the rate limit actually drifted — an unconditional /set every
            // minute would be a needless write against live hardware for no reason. Matched via
            // a closure, NOT Collection::firstWhere('.id', ...) — firstWhere() resolves keys
            // through data_get()'s dot-notation, and a key that itself starts with a literal
            // dot (RouterOS's own ".id" field) splits into a leading empty segment and never
            // matches, silently defeating the whole idempotency check. Confirmed against real
            // hardware: firstWhere('.id', $id) reliably returned null even for a row that was
            // demonstrably present in the same response.
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

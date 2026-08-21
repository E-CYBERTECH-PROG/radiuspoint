<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Console\Command;

/**
 * Creates one realistic demo tenant with routers/plans/customers/transactions, for local
 * development and manual testing — replaces the one-off tinker scripts this project has
 * otherwise relied on for QA data. Refuses to touch a production database without an explicit,
 * confirmed --force, the same convention Laravel's own migrate --force uses.
 */
class SeedDemoData extends Command
{
    protected $signature = 'demo:seed
        {--force : Required to run against APP_ENV=production}
        {--tenant= : Seed into this existing tenant ID instead of creating a new demo tenant}';

    protected $description = 'Seed a demo tenant with routers, plans, hotspot/PPPoE customers, and transactions';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to seed demo data into a production environment without --force.');

            return self::FAILURE;
        }

        $counts = ['routers' => 3, 'hotspot_plans' => 3, 'pppoe_plans' => 2, 'hotspot_users' => 8, 'pppoe_users' => 5, 'transactions' => 10];

        if (app()->environment('production')) {
            $this->warn('This will create a demo tenant with '.
                "{$counts['routers']} routers, {$counts['hotspot_plans']} hotspot + {$counts['pppoe_plans']} pppoe plans, ".
                "{$counts['hotspot_users']} hotspot users, {$counts['pppoe_users']} pppoe users, and {$counts['transactions']} transactions ".
                'on the PRODUCTION database.');

            if (! $this->confirm('Continue?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        if ($tenantId = $this->option('tenant')) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                $this->error("Tenant #{$tenantId} not found.");

                return self::FAILURE;
            }
        } else {
            $tenant = Tenant::factory()->create([
                'company_name' => 'Demo ISP ('.now()->format('Y-m-d H:i').')',
            ]);
        }

        $routers = Router::factory()->count($counts['routers'])->create(['tenant_id' => $tenant->id]);

        $plans = Plan::factory()->count($counts['hotspot_plans'])->create(['tenant_id' => $tenant->id])
            ->merge(Plan::factory()->pppoe()->count($counts['pppoe_plans'])->create(['tenant_id' => $tenant->id]));

        $hotspotPlans = $plans->where('type', 'hotspot');
        $pppoePlans = $plans->where('type', 'pppoe');

        // Split across active/expired/offline (roughly 60/15/25%) rather than leaving every
        // record on the factory's 'offline' default — an all-offline demo tenant makes the
        // dashboard's Active/Expired tiles look broken even though they're working correctly.
        $this->seedCustomersWithStatusMix(HotspotUser::factory(), $counts['hotspot_users'], [
            'tenant_id' => $tenant->id,
            'current_plan_id' => fn () => $hotspotPlans->random()->id,
            'current_router_id' => fn () => $routers->random()->id,
        ]);

        $this->seedCustomersWithStatusMix(PppoeUser::factory(), $counts['pppoe_users'], [
            'tenant_id' => $tenant->id,
            'current_plan_id' => fn () => $pppoePlans->random()->id,
            'current_router_id' => fn () => $routers->random()->id,
        ]);

        Transaction::factory()->count($counts['transactions'])->create(['tenant_id' => $tenant->id]);

        $this->info("Seeded tenant #{$tenant->id} \"{$tenant->company_name}\" with ".
            "{$counts['routers']} routers, ".($counts['hotspot_plans'] + $counts['pppoe_plans']).' plans, '.
            "{$counts['hotspot_users']} hotspot users, {$counts['pppoe_users']} pppoe users, {$counts['transactions']} transactions.");

        return self::SUCCESS;
    }

    /**
     * Creates $count records split ~60/15/25 across active/expired/offline, so demo tenants
     * populate every status tile instead of leaving them all on the factory's 'offline' default.
     * $overrides values may be closures (evaluated per record, e.g. Collection::random() picks).
     */
    private function seedCustomersWithStatusMix($factory, int $count, array $overrides): void
    {
        $active = (int) round($count * 0.6);
        $expired = (int) round($count * 0.15);
        $offline = max(0, $count - $active - $expired);

        $resolve = fn () => collect($overrides)->map(fn ($v) => $v instanceof \Closure ? $v() : $v)->all();

        if ($active > 0) {
            $factory->count($active)->active()->create($resolve);
        }
        if ($expired > 0) {
            $factory->count($expired)->expired()->create($resolve);
        }
        if ($offline > 0) {
            $factory->count($offline)->create($resolve);
        }
    }
}

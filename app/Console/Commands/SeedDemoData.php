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
    protected $signature = 'demo:seed {--force : Required to run against APP_ENV=production}';

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

        $tenant = Tenant::factory()->create([
            'company_name' => 'Demo ISP ('.now()->format('Y-m-d H:i').')',
        ]);

        $routers = Router::factory()->count($counts['routers'])->create(['tenant_id' => $tenant->id]);

        $plans = Plan::factory()->count($counts['hotspot_plans'])->create(['tenant_id' => $tenant->id])
            ->merge(Plan::factory()->pppoe()->count($counts['pppoe_plans'])->create(['tenant_id' => $tenant->id]));

        $hotspotPlans = $plans->where('type', 'hotspot');
        $pppoePlans = $plans->where('type', 'pppoe');

        HotspotUser::factory()->count($counts['hotspot_users'])->create(fn () => [
            'tenant_id' => $tenant->id,
            'current_plan_id' => $hotspotPlans->random()->id,
            'current_router_id' => $routers->random()->id,
        ]);

        PppoeUser::factory()->count($counts['pppoe_users'])->create(fn () => [
            'tenant_id' => $tenant->id,
            'current_plan_id' => $pppoePlans->random()->id,
            'current_router_id' => $routers->random()->id,
        ]);

        Transaction::factory()->count($counts['transactions'])->create(['tenant_id' => $tenant->id]);

        $this->info("Seeded tenant #{$tenant->id} \"{$tenant->company_name}\" with ".
            "{$counts['routers']} routers, ".($counts['hotspot_plans'] + $counts['pppoe_plans']).' plans, '.
            "{$counts['hotspot_users']} hotspot users, {$counts['pppoe_users']} pppoe users, {$counts['transactions']} transactions.");

        return self::SUCCESS;
    }
}

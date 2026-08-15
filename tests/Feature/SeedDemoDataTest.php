<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_realistic_demo_tenant(): void
    {
        $this->artisan('demo:seed')->assertSuccessful();

        $tenant = Tenant::latest('id')->first();

        $this->assertNotNull($tenant);
        $this->assertSame(3, Router::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(5, Plan::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(8, HotspotUser::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(5, PppoeUser::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(10, Transaction::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count());
    }

    public function test_it_refuses_to_run_in_production_without_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('demo:seed')->assertFailed();

        $this->assertSame(0, Tenant::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_deactivate_and_activate(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $plans = Plan::factory()->count(2)->create(['tenant_id' => $tenant->id, 'status' => 'active']);

        $this->actingAs($admin)->post('/plans/bulk', ['plan_ids' => $plans->pluck('id')->all(), 'action' => 'deactivate'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(['inactive', 'inactive'], Plan::pluck('status')->all());

        $this->actingAs($admin)->post('/plans/bulk', ['plan_ids' => $plans->pluck('id')->all(), 'action' => 'activate']);
        $this->assertSame(['active', 'active'], Plan::pluck('status')->all());
    }

    public function test_bulk_delete_skips_packages_still_assigned_to_customers(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $used = Plan::factory()->create(['tenant_id' => $tenant->id]);
        $unused = Plan::factory()->create(['tenant_id' => $tenant->id]);
        HotspotUser::factory()->create(['tenant_id' => $tenant->id, 'current_plan_id' => $used->id]);

        $this->actingAs($admin)->post('/plans/bulk', ['plan_ids' => [$used->id, $unused->id], 'action' => 'delete'])
            ->assertSessionHas('error', 'Removed 1 package(s). Skipped 1 still assigned to customers.');

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
    }

    public function test_bulk_actions_never_touch_another_tenants_packages(): void
    {
        $mine = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $mine->id]);
        $theirs = Plan::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'status' => 'active']);

        $this->actingAs($admin)->post('/plans/bulk', ['plan_ids' => [$theirs->id], 'action' => 'delete']);
        $this->actingAs($admin)->post('/plans/bulk', ['plan_ids' => [$theirs->id], 'action' => 'deactivate']);

        $this->assertModelExists($theirs);
        $this->assertSame('active', $theirs->fresh()->status);
    }

    public function test_packages_page_renders_row_actions_outside_the_search_form(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot']);

        $this->actingAs($admin)->get('/plans?tab=hotspot')
            ->assertOk()
            ->assertSee('form="rp-plan-dup-'.$plan->id.'"', false)
            ->assertSee('id="rp-plan-dup-'.$plan->id.'"', false)
            ->assertSee('form="rp-plans-bulk"', false);
    }
}

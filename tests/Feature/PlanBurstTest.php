<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanBurstTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_is_unchanged_for_a_plan_without_burst(): void
    {
        $plan = new Plan(['speed_limit' => '5M/5M']);

        $this->assertSame('5M/5M', $plan->rate_limit);
    }

    public function test_rate_limit_adds_burst_rate_threshold_and_time(): void
    {
        $plan = new Plan(['speed_limit' => '2M/5M', 'burst_limit' => '4M/10M', 'burst_time' => 8]);

        // Threshold is 75% of the normal rate on each side, in decimal RouterOS units.
        $this->assertSame('2M/5M 4M/10M 1500k/3750k 8/8', $plan->rate_limit);
    }

    public function test_package_can_be_created_with_burst_and_it_reaches_radius(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->post('/plans', [
            'name' => 'Boost 5M', 'type' => 'hotspot', 'price' => 50,
            'duration_value' => 1, 'duration_unit' => 'days',
            'upload_speed' => '5M', 'download_speed' => '5M',
            'burst_upload' => '10M', 'burst_download' => '10M', 'burst_time' => 6,
        ])->assertRedirect();

        $plan = Plan::where('name', 'Boost 5M')->sole();
        $this->assertSame('10M/10M', $plan->burst_limit);
        $this->assertSame(6, $plan->burst_time);

        $customer = HotspotUser::factory()->create([
            'tenant_id' => $tenant->id, 'phone_number' => '254722000333', 'current_plan_id' => $plan->id,
        ]);
        $this->actingAs($admin)->post("/hotspot-users/{$customer->id}/extend", ['days' => 1]);

        $this->assertSame(
            '5M/5M 10M/10M 3750k/3750k 6/6',
            DB::table('radreply')->where('username', '254722000333')->where('attribute', 'Mikrotik-Rate-Limit')->value('value')
        );
    }

    public function test_burst_slower_than_the_normal_speed_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->post('/plans', [
            'name' => 'Bad burst', 'type' => 'hotspot', 'price' => 50,
            'duration_value' => 1, 'duration_unit' => 'days',
            'upload_speed' => '5M', 'download_speed' => '5M',
            'burst_upload' => '2M', 'burst_download' => '10M',
        ])->assertSessionHasErrors('burst_upload');

        $this->assertDatabaseMissing('plans', ['name' => 'Bad burst']);
    }

    public function test_clearing_burst_on_edit_removes_it(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create([
            'tenant_id' => $tenant->id, 'type' => 'hotspot', 'speed_limit' => '5M/5M',
            'burst_limit' => '10M/10M', 'burst_time' => 8,
        ]);

        $this->actingAs($admin)->put("/plans/{$plan->id}", [
            'name' => $plan->name, 'type' => 'hotspot', 'status' => 'active', 'price' => $plan->price,
            'duration_value' => 1, 'duration_unit' => 'days',
            'upload_speed' => '5M', 'download_speed' => '5M',
        ])->assertRedirect();

        $plan->refresh();
        $this->assertNull($plan->burst_limit);
        $this->assertSame('5M/5M', $plan->rate_limit);
    }
}

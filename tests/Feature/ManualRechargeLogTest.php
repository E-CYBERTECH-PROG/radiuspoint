<?php

namespace Tests\Feature;

use App\Models\BalanceAdjustment;
use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualRechargeLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_extending_a_hotspot_customer_is_logged_with_before_and_after_expiry(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Grace Admin']);
        $customer = HotspotUser::factory()->create([
            'tenant_id' => $tenant->id,
            'phone_number' => '254711000111',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->post("/hotspot-users/{$customer->id}/extend", ['days' => 3])->assertRedirect();

        $entry = BalanceAdjustment::withoutGlobalScopes()->sole();
        $this->assertSame('extension', $entry->kind);
        $this->assertSame($customer->id, $entry->hotspot_user_id);
        $this->assertSame($admin->id, $entry->created_by);
        $this->assertNull($entry->amount);
        $this->assertTrue($entry->previous_expires_at->isPast());
        $this->assertTrue($entry->new_expires_at->isFuture());
        $this->assertSame('Extended by 3 day(s)', $entry->reason);
    }

    public function test_report_lists_wallet_and_extension_entries_and_filters_by_kind(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Grace Admin']);
        $pppoe = PppoeUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'kamau_home']);

        $this->actingAs($admin)->post("/pppoe-users/{$pppoe->id}/adjust-balance", [
            'type' => 'credit', 'amount' => 250, 'reason' => 'Paid cash at office',
        ]);
        $this->actingAs($admin)->post("/pppoe-users/{$pppoe->id}/extend", ['days' => 7]);

        $this->actingAs($admin)->get('/reports/manual-recharges')
            ->assertOk()
            ->assertSee('kamau_home')
            ->assertSee('Paid cash at office')
            ->assertSee('Extended by 7 day(s)')
            ->assertSee('Grace Admin')
            ->assertSee('250.00');

        $this->actingAs($admin)->get('/reports/manual-recharges?kind=extension')
            ->assertOk()
            ->assertSee('Extended by 7 day(s)')
            ->assertDontSee('Paid cash at office');
    }

    public function test_report_never_shows_another_tenants_entries(): void
    {
        $mine = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $mine->id]);
        $otherCustomer = PppoeUser::factory()->create(['tenant_id' => $other->id, 'username' => 'someone_elses']);
        BalanceAdjustment::withoutGlobalScopes()->create([
            'tenant_id' => $other->id, 'pppoe_user_id' => $otherCustomer->id,
            'kind' => 'balance', 'amount' => 999, 'reason' => 'Other tenant credit',
        ]);

        $this->actingAs($admin)->get('/reports/manual-recharges')
            ->assertOk()
            ->assertDontSee('someone_elses')
            ->assertDontSee('Other tenant credit');
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerController;
use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the split of the old single "Customers" hub (/customers?tab=x) into two separate
 * pages (/customers/pppoe, /customers/hotspot) reachable from the sidebar's CRM > Customers
 * submenu — see routes/web.php and CustomerController::index()/show().
 */
class CustomersSplitRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pppoe_customers_page_lists_only_pppoe_customers(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        PppoeUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'ppp-only-user']);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => 'hotspot-only-user']);

        $response = $this->actingAs($admin)->get('/customers/pppoe');

        $response->assertOk();
        $response->assertSee('ppp-only-user');
        $response->assertDontSee('hotspot-only-user');
    }

    public function test_hotspot_customers_page_lists_only_hotspot_customers(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        PppoeUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'ppp-only-user-2']);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => 'hotspot-only-user-2']);

        $response = $this->actingAs($admin)->get('/customers/hotspot');

        $response->assertOk();
        $response->assertSee('hotspot-only-user-2');
        $response->assertDontSee('ppp-only-user-2');
    }

    public function test_pppoe_customer_detail_page_resolves_at_its_own_url(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = PppoeUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'detail-ppp-user']);
        $token = CustomerController::tokenFor('pppoe', $customer->id);

        $response = $this->actingAs($admin)->get("/customers/pppoe/view/{$token}");

        $response->assertOk();
        $response->assertSee('detail-ppp-user');
    }

    public function test_hotspot_customer_detail_page_resolves_at_its_own_url(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = HotspotUser::factory()->create(['tenant_id' => $tenant->id, 'phone_number' => '254799887766']);
        $token = CustomerController::tokenFor('hotspot', $customer->id);

        $response = $this->actingAs($admin)->get("/customers/hotspot/view/{$token}");

        $response->assertOk();
        $response->assertSee('254799887766');
    }

    public function test_a_pppoe_token_used_under_the_hotspot_url_404s(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = PppoeUser::factory()->create(['tenant_id' => $tenant->id]);
        $token = CustomerController::tokenFor('pppoe', $customer->id);

        $this->actingAs($admin)->get("/customers/hotspot/view/{$token}")->assertNotFound();
    }

    public function test_an_invalid_type_segment_404s(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->get('/customers/whatever')->assertNotFound();
    }

    public function test_sidebar_shows_pppoe_and_hotspot_as_customers_submenu_items(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($admin)->get('/customers/pppoe');

        $response->assertOk();
        $response->assertSee('Customers');
        $response->assertSee('PPPoE');
        $response->assertSee('Hotspot');
    }
}

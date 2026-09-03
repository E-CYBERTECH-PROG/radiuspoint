<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerController;
use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RadiusSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HotspotCustomerDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotspot_detail_page_shows_kpi_strip_and_live_device_session_data(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Langas 4011', 'ip_address' => '10.0.0.5']);
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot', 'name' => 'Unlimited 6HRS']);
        $customer = HotspotUser::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'phone_number' => '254799112233',
            'mac_address' => '62:61:2B:B9:1E:2C',
            'current_plan_id' => $plan->id,
            'current_router_id' => $router->id,
        ]);

        // An open (still-connected) accounting session for this device.
        DB::table('radacct')->insert([
            'acctsessionid' => 'sess-1',
            'acctuniqueid' => 'uniq-1',
            'username' => $customer->phone_number,
            'nasipaddress' => '10.0.0.5',
            'acctstarttime' => now()->subMinutes(10),
            'acctstoptime' => null,
            'framedipaddress' => '11.220.3.190',
            'callingstationid' => '62:61:2B:B9:1E:2C',
            'acctinputoctets' => 1000,
            'acctoutputoctets' => 2000,
        ]);

        $token = CustomerController::tokenFor('hotspot', $customer->id);
        $response = $this->actingAs($admin)->get("/customers/hotspot/view/{$token}");

        $response->assertOk();
        $response->assertSee('254799112233');
        $response->assertSee('62:61:2B:B9:1E:2C');
        $response->assertSee('Online');
        $response->assertSee('Langas 4011');
        $response->assertSee('11.220.3.190');
        $response->assertSee('Unlimited 6HRS');
        $response->assertSee('Purge');
        $response->assertSee('Device Session Data');
        $response->assertSee('Quick Action Buttons');
    }

    public function test_hotspot_detail_page_handles_a_customer_with_no_session_history(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = HotspotUser::factory()->create(['tenant_id' => $tenant->id, 'phone_number' => '254799445566']);

        $token = CustomerController::tokenFor('hotspot', $customer->id);
        $response = $this->actingAs($admin)->get("/customers/hotspot/view/{$token}");

        $response->assertOk();
        $response->assertSee('No session data yet');
        $response->assertSee('Offline');
    }

    public function test_pppoe_detail_page_is_unaffected_by_the_hotspot_redesign(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = PppoeUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'ppp-detail-user']);

        $token = CustomerController::tokenFor('pppoe', $customer->id);
        $response = $this->actingAs($admin)->get("/customers/pppoe/view/{$token}");

        $response->assertOk();
        $response->assertSee('ppp-detail-user');
        $response->assertDontSee('Device Session Data');
        $response->assertDontSee('Quick Action Buttons');
    }

    public function test_purge_disconnects_removes_credential_and_clears_history_but_keeps_the_record(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => '254799778899', 'mac_address' => 'AA:BB:CC:DD:EE:FF']);

        RadiusSyncService::sync($customer->phone_number, 'somepass', null);
        DB::table('radacct')->insert([
            'acctsessionid' => 'sess-2',
            'acctuniqueid' => 'uniq-2',
            'username' => $customer->phone_number,
            'nasipaddress' => '10.0.0.9',
            'acctstarttime' => now()->subHour(),
            'acctstoptime' => now(),
        ]);

        $this->actingAs($admin)->post(route('hotspot-users.purge', $customer))->assertRedirect();

        $this->assertDatabaseHas('hotspot_users', ['id' => $customer->id, 'status' => 'offline', 'mac_address' => null]);
        $this->assertSame(0, DB::table('radcheck')->where('username', '254799778899')->count());
        $this->assertSame(0, DB::table('radacct')->where('username', '254799778899')->count());
    }
}

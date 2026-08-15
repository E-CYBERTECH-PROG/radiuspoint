<?php

namespace Tests\Feature;

use App\Models\PppoeUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PppoeUserTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Same boot-order caveat as HotspotUserTenantScopeTest — see its setUp() for why.
        Model::clearBootedModels();
    }

    public function test_authenticated_user_only_sees_their_own_tenants_pppoe_users(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA = User::factory()->for($tenantA)->create();

        // Raw insert, not the PppoeUser model — see HotspotUserTenantScopeTest for why creating
        // another tenant's row through Eloquent isn't possible once actingAs() has run.
        DB::table('pppoe_users')->insert([
            ['tenant_id' => $tenantB->id, 'username' => 'demo_b_1', 'status' => 'offline', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantB->id, 'username' => 'demo_b_2', 'status' => 'offline', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantB->id, 'username' => 'demo_b_3', 'status' => 'offline', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($userA);
        PppoeUser::factory()->count(2)->create(['tenant_id' => $tenantA->id]);

        $this->assertSame(2, PppoeUser::count());
        $this->assertTrue(PppoeUser::all()->every(fn ($u) => $u->tenant_id === $tenantA->id));
    }

    public function test_creating_as_an_authenticated_user_auto_assigns_their_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();

        $this->actingAs($user);

        $created = PppoeUser::create(['username' => 'demo_customer_1']);

        $this->assertSame($tenant->id, $created->tenant_id);
    }
}

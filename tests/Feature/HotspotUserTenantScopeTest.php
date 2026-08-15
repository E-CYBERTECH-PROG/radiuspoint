<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HotspotUserTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // BelongsToTenant only registers its global scope the first time HotspotUser::class
        // boots AND auth()->check() is true at that exact moment (see
        // app/Traits/BelongsToTenant.php) — that result is cached in Eloquent's static $booted
        // array for the rest of this PHP process, so touching the model before actingAs() below
        // would permanently disable scoping for every later test in this run. Not a risk in real
        // requests (php-fpm boots a fresh process per request, so the very first touch is always
        // after auth middleware runs), but very much a risk within one PHPUnit process/method.
        Model::clearBootedModels();
    }

    public function test_authenticated_user_only_sees_their_own_tenants_hotspot_users(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA = User::factory()->for($tenantA)->create();

        // Tenant B's rows are seeded via a raw insert, not the HotspotUser model — the
        // BelongsToTenant "creating" hook unconditionally overwrites tenant_id to the *acting*
        // user's tenant once authenticated (see test below), so there's no way to create
        // another tenant's row through Eloquent once actingAs() has run. Standing in for data
        // that already existed from tenant B's own, separate session.
        DB::table('hotspot_users')->insert([
            ['tenant_id' => $tenantB->id, 'phone_number' => '254700000101', 'status' => 'offline', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantB->id, 'phone_number' => '254700000102', 'status' => 'offline', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenantB->id, 'phone_number' => '254700000103', 'status' => 'offline', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($userA);
        HotspotUser::factory()->count(2)->create(['tenant_id' => $tenantA->id]);

        $this->assertSame(2, HotspotUser::count());
        $this->assertTrue(HotspotUser::all()->every(fn ($u) => $u->tenant_id === $tenantA->id));
    }

    public function test_creating_as_an_authenticated_user_auto_assigns_their_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create();

        $this->actingAs($user);

        $created = HotspotUser::create(['phone_number' => '254700000099']);

        $this->assertSame($tenant->id, $created->tenant_id);
    }
}

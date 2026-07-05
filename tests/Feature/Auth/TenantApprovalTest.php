<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_pending_tenant_is_redirected_to_pending_approval_page(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'pending']);
        $user = User::factory()->for($tenant)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('tenant.pending'));
    }

    public function test_platform_admin_can_approve_a_pending_tenant(): void
    {
        $platformTenant = Tenant::factory()->create(['status' => 'active']);
        $platformAdmin = User::factory()->for($platformTenant)->create(['is_platform_admin' => true]);

        $tenant = Tenant::factory()->create(['status' => 'pending']);
        $user = User::factory()->for($tenant)->create();

        $response = $this->actingAs($platformAdmin)
            ->post(route('platform-admin.tenants.approve', $tenant));

        $response->assertRedirect(route('platform-admin.tenants.index'));
        $this->assertSame('active', $tenant->fresh()->status);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_non_platform_admin_cannot_access_platform_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform-admin.tenants.index'))
            ->assertForbidden();
    }
}

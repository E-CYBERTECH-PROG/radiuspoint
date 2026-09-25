<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Services\ExpiredBlockService;
use App\Services\MikrotikApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ExpiredBlockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_every_matching_address_list_entry(): void
    {
        $router = Router::factory()->create();

        $api = Mockery::mock(MikrotikApiService::class);
        $api->shouldReceive('connect')->once()
            ->with($router->ip_address, $router->api_username, $router->api_password)
            ->andReturnTrue();
        // No active session found under this username on either table, so there's no "current
        // IP" to match by — falls back to the comment-based match alone.
        $api->shouldReceive('query')->once()->with('/ip/hotspot/active/print')->andReturn([]);
        $api->shouldReceive('query')->once()->with('/ppp/active/print')->andReturn([]);
        $api->shouldReceive('queryWhere')->once()
            ->with('/ip/firewall/address-list/print', 'comment', 'expired: 254700000001')
            ->andReturn([
                ['.id' => '*1', 'comment' => 'expired: 254700000001'],
                ['.id' => '*2', 'comment' => 'expired: 254700000001'],
            ]);
        $api->shouldReceive('query')->once()->with('/ip/firewall/address-list/remove', ['.id' => '*1']);
        $api->shouldReceive('query')->once()->with('/ip/firewall/address-list/remove', ['.id' => '*2']);

        $this->app->instance(MikrotikApiService::class, $api);

        ExpiredBlockService::clear($router, '254700000001');
    }

    /**
     * The real incident this covers: a DHCP pool recycles IPs across completely different
     * customers, so the block sitting on the address a reconnecting device just got handed is
     * very often still tagged with somebody else's username from whenever *they* expired —
     * confirmed live, voucher "790" came online on an address still blocked under "expired: 90"
     * from an unrelated customer hours earlier. Matching by comment alone would never have
     * found that block, since there's no "expired: 790" entry to match. This has to match by
     * the customer's actual current IP too.
     */
    public function test_it_also_clears_a_block_left_on_the_customers_current_ip_under_a_different_name(): void
    {
        $router = Router::factory()->create();

        $api = Mockery::mock(MikrotikApiService::class);
        $api->shouldReceive('connect')->once()->andReturnTrue();
        $api->shouldReceive('query')->once()->with('/ip/hotspot/active/print')->andReturn([
            ['user' => '790', 'address' => '10.100.0.252'],
        ]);
        $api->shouldReceive('queryWhere')->once()
            ->with('/ip/firewall/address-list/print', 'address', '10.100.0.252')
            ->andReturn([
                ['.id' => '*9', 'address' => '10.100.0.252', 'comment' => 'expired: 90'],
            ]);
        $api->shouldReceive('query')->once()->with('/ip/firewall/address-list/remove', ['.id' => '*9']);
        // Also still checks by this customer's own username as a fallback.
        $api->shouldReceive('queryWhere')->once()
            ->with('/ip/firewall/address-list/print', 'comment', 'expired: 790')
            ->andReturn([]);

        $this->app->instance(MikrotikApiService::class, $api);

        ExpiredBlockService::clear($router, '790');
    }

    public function test_it_is_a_silent_no_op_without_a_router(): void
    {
        $api = Mockery::mock(MikrotikApiService::class);
        $api->shouldNotReceive('connect');

        $this->app->instance(MikrotikApiService::class, $api);

        ExpiredBlockService::clear(null, '254700000002');

        $this->assertTrue(true);
    }

    public function test_it_does_not_throw_when_the_router_is_unreachable(): void
    {
        $router = Router::factory()->create();

        $api = Mockery::mock(MikrotikApiService::class);
        $api->shouldReceive('connect')->once()->andReturnFalse();
        $api->shouldNotReceive('queryWhere');

        $this->app->instance(MikrotikApiService::class, $api);

        ExpiredBlockService::clear($router, '254700000003');

        $this->assertTrue(true);
    }
}

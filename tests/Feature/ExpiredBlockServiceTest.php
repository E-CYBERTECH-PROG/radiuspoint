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

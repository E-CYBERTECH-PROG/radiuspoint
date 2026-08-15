<?php

namespace Database\Factories;

use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Router>
 */
class RouterFactory extends Factory
{
    protected $model = Router::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->city().' Router',
            'ip_address' => fake()->unique()->localIpv4(),
            'api_username' => 'demo_adm_'.Str::random(5),
            'api_password' => Str::random(12),
            'secret_key' => Str::random(32),
            'status' => 'offline',
        ];
    }
}

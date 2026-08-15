<?php

namespace Database\Factories;

use App\Models\HotspotUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HotspotUser>
 */
class HotspotUserFactory extends Factory
{
    protected $model = HotspotUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'phone_number' => '2547'.fake()->numerify('########'),
            'mac_address' => fake()->macAddress(),
            'current_plan_id' => null,
            'current_router_id' => null,
            'status' => 'offline',
            'expires_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => now()->subHour(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\PppoeUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PppoeUser>
 */
class PppoeUserFactory extends Factory
{
    protected $model = PppoeUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'phone_number' => '2547'.fake()->numerify('########'),
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
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }
}

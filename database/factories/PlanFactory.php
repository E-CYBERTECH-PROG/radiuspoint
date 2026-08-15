<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['Quick Browse', 'Day Pass', 'Weekly Unlimited', 'Monthly Home']),
            'type' => 'hotspot',
            'price' => fake()->randomElement([10, 20, 50, 100, 250, 1000]),
            'duration_value' => 1,
            'duration_unit' => fake()->randomElement(Plan::DURATION_UNITS),
            'data_cap_mb' => null,
            'speed_limit' => fake()->randomElement(['2M/2M', '5M/5M', '10M/10M']),
        ];
    }

    public function pppoe(): static
    {
        return $this->state(fn () => [
            'type' => 'pppoe',
            'duration_unit' => 'months',
            'price' => fake()->randomElement([1500, 2500, 3500]),
            'speed_limit' => fake()->randomElement(['10M/10M', '20M/20M', '40M/40M']),
        ]);
    }
}

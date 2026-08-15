<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_name' => fake()->name(),
            'phone_number' => '2547'.fake()->numerify('########'),
            'package_name' => fake()->randomElement(['Quick Browse', 'Day Pass', 'Weekly Unlimited']),
            'amount' => fake()->randomElement([10, 20, 50, 100, 250]),
            'payment_method' => 'M-Pesa STK',
            'status' => 'success',
            'mpesa_receipt' => strtoupper(fake()->bothify('??########')),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'mpesa_receipt' => null,
        ]);
    }
}

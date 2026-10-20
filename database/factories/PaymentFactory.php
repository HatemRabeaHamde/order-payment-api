<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::Pending,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'transaction_reference' => null,
            'gateway_response' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Successful,
            'transaction_reference' => fake()->uuid(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'transaction_reference' => fake()->uuid(),
        ]);
    }
}

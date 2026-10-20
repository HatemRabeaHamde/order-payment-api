<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $price = fake()->randomFloat(2, 5, 500);

        return [
            'order_id' => Order::factory(),
            'product_name' => fake()->words(3, true),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => round($quantity * $price, 2),
        ];
    }
}

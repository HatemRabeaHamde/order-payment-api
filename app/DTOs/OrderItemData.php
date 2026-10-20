<?php

namespace App\DTOs;

final class OrderItemData
{
    public function __construct(
        public readonly string $productName,
        public readonly int $quantity,
        public readonly float $price,
    ) {}

    /**
     * @param  array{product_name: string, quantity: int, price: float}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productName: $data['product_name'],
            quantity: (int) $data['quantity'],
            price: (float) $data['price'],
        );
    }

    public function subtotal(): float
    {
        return round($this->price * $this->quantity, 2);
    }
}

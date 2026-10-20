<?php

namespace App\DTOs;

final class CreateOrderData
{
    /**
     * @param  array<int, OrderItemData>  $items
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly array $items,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        $items = array_map(
            fn (array $item) => OrderItemData::fromArray($item),
            $data['items'],
        );

        return new self(
            userId: $userId,
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            items: $items,
        );
    }
}

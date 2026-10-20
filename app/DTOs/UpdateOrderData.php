<?php

namespace App\DTOs;

use App\Enums\OrderStatus;

final class UpdateOrderData
{
    /**
     * @param  array<int, OrderItemData>|null  $items
     */
    public function __construct(
        public readonly ?string $customerName,
        public readonly ?string $customerEmail,
        public readonly ?OrderStatus $status,
        public readonly ?array $items,
    ) {}

    public static function fromRequest(array $data): self
    {
        $items = isset($data['items'])
            ? array_map(fn (array $item) => OrderItemData::fromArray($item), $data['items'])
            : null;

        return new self(
            customerName: $data['customer_name'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            status: isset($data['status']) ? OrderStatus::from($data['status']) : null,
            items: $items,
        );
    }
}

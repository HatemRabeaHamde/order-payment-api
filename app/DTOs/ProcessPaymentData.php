<?php

namespace App\DTOs;

use App\Enums\PaymentMethod;

final class ProcessPaymentData
{
    public function __construct(
        public readonly int $orderId,
        public readonly PaymentMethod $method,
    ) {}

    public static function fromRequest(array $data, int $orderId): self
    {
        return new self(
            orderId: $orderId,
            method: PaymentMethod::from($data['method']),
        );
    }
}

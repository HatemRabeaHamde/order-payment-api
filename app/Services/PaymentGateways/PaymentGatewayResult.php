<?php

namespace App\Services\PaymentGateways;

final class PaymentGatewayResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $transactionReference,
        public readonly array $raw = [],
    ) {}

    public static function success(string $transactionReference, array $raw = []): self
    {
        return new self(successful: true, transactionReference: $transactionReference, raw: $raw);
    }

    public static function failure(string $transactionReference, array $raw = []): self
    {
        return new self(successful: false, transactionReference: $transactionReference, raw: $raw);
    }
}

<?php

namespace App\Services\PaymentGateways;

use App\Enums\PaymentMethod;
use App\Exceptions\UnsupportedPaymentGatewayException;
use Illuminate\Contracts\Container\Container;

final class PaymentGatewayManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function resolve(PaymentMethod $method): PaymentGatewayInterface
    {
        $gateways = config('payment_gateways.gateways', []);

        $gatewayClass = $gateways[$method->value]['class'] ?? null;

        if ($gatewayClass === null || ! is_a($gatewayClass, PaymentGatewayInterface::class, true)) {
            throw new UnsupportedPaymentGatewayException($method->value);
        }

        return $this->container->make($gatewayClass, [
            'apiKey' => $gateways[$method->value]['key'] ?? null,
            'apiSecret' => $gateways[$method->value]['secret'] ?? null,
        ]);
    }
}

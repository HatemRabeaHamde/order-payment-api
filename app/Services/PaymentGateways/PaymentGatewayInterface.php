<?php

namespace App\Services\PaymentGateways;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function charge(Payment $payment): PaymentGatewayResult;
}

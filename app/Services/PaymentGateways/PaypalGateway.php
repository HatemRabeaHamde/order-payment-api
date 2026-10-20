<?php

namespace App\Services\PaymentGateways;

use App\Models\Payment;
use Illuminate\Support\Str;

final class PaypalGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $apiSecret,
    ) {}

    public function charge(Payment $payment): PaymentGatewayResult
    {
        $reference = 'pp_'.Str::uuid()->toString();

        return PaymentGatewayResult::success($reference, [
            'gateway' => 'paypal',
            'amount' => (float) $payment->amount,
            'order_id' => $payment->order_id,
        ]);
    }
}

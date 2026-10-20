<?php

namespace App\Services\PaymentGateways;

use App\Models\Payment;
use Illuminate\Support\Str;

final class CreditCardGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $apiSecret,
    ) {}

    public function charge(Payment $payment): PaymentGatewayResult
    {
        $reference = 'cc_'.Str::uuid()->toString();

        return PaymentGatewayResult::success($reference, [
            'gateway' => 'credit_card',
            'amount' => (float) $payment->amount,
            'order_id' => $payment->order_id,
        ]);
    }
}

<?php

namespace App\Services;

use App\DTOs\ProcessPaymentData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderNotConfirmedException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGateways\PaymentGatewayManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function listForOrder(Order $order, int $perPage): LengthAwarePaginator
    {
        return $order->payments()->latest()->paginate($perPage);
    }

    public function listAll(int $perPage): LengthAwarePaginator
    {
        return Payment::query()->with('order')->latest()->paginate($perPage);
    }

    public function process(Order $order, ProcessPaymentData $data): Payment
    {
        if ($order->status !== OrderStatus::Confirmed) {
            throw new OrderNotConfirmedException;
        }

        $payment = DB::transaction(function () use ($order, $data) {
            return $order->payments()->create([
                'method' => $data->method,
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
            ]);
        });

        $gateway = $this->gatewayManager->resolve($data->method);
        $result = $gateway->charge($payment);

        $payment->update([
            'status' => $result->successful ? PaymentStatus::Successful : PaymentStatus::Failed,
            'transaction_reference' => $result->transactionReference,
            'gateway_response' => $result->raw,
        ]);

        return $payment->fresh();
    }
}

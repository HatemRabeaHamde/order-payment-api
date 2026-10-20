<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\UnsupportedPaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentGateways\PaypalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_credit_card_gateway(): void
    {
        $manager = $this->app->make(PaymentGatewayManager::class);

        $gateway = $manager->resolve(PaymentMethod::CreditCard);

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    public function test_it_resolves_the_paypal_gateway(): void
    {
        $manager = $this->app->make(PaymentGatewayManager::class);

        $gateway = $manager->resolve(PaymentMethod::Paypal);

        $this->assertInstanceOf(PaypalGateway::class, $gateway);
    }

    public function test_it_throws_when_gateway_is_not_configured(): void
    {
        config(['payment_gateways.gateways.credit_card.class' => null]);

        $manager = $this->app->make(PaymentGatewayManager::class);

        $this->expectException(UnsupportedPaymentGatewayException::class);

        $manager->resolve(PaymentMethod::CreditCard);
    }

    public function test_resolved_gateway_returns_a_successful_charge_result(): void
    {
        $order = Order::factory()->confirmed()->create(['total' => 75]);
        $payment = Payment::factory()->make(['order_id' => $order->id, 'amount' => 75]);
        $payment->setRelation('order', $order);

        $manager = $this->app->make(PaymentGatewayManager::class);
        $gateway = $manager->resolve(PaymentMethod::CreditCard);

        $result = $gateway->charge($payment);

        $this->assertTrue($result->successful);
        $this->assertNotEmpty($result->transactionReference);
    }

    public function test_every_configured_gateway_implements_the_strategy_interface(): void
    {
        foreach (array_keys(config('payment_gateways.gateways')) as $method) {
            $manager = $this->app->make(PaymentGatewayManager::class);
            $gateway = $manager->resolve(PaymentMethod::from($method));

            $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        }
    }
}

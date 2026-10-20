<?php

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_payment_cannot_be_processed_for_pending_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => OrderStatus::Pending]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/orders/{$order->id}/payments", ['method' => 'credit_card']);

        $response->assertStatus(409)
            ->assertJson(['message' => 'Payments can only be processed for orders in the confirmed status.']);
    }

    public function test_payment_can_be_processed_for_confirmed_order_via_credit_card(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id, 'total' => 100]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/orders/{$order->id}/payments", ['method' => 'credit_card']);

        $response->assertCreated()
            ->assertJsonPath('data.method', 'credit_card')
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.amount', 100);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'credit_card',
            'status' => 'successful',
        ]);
    }

    public function test_payment_can_be_processed_via_paypal(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id, 'total' => 50]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/orders/{$order->id}/payments", ['method' => 'paypal']);

        $response->assertCreated()->assertJsonPath('data.method', 'paypal');
    }

    public function test_payment_requires_a_valid_method(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/orders/{$order->id}/payments", ['method' => 'bitcoin']);

        $response->assertStatus(422)->assertJsonValidationErrors(['method']);
    }

    public function test_user_can_view_payments_for_a_specific_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/orders/{$order->id}/payments");

        $response->assertOk()->assertJsonCount(2, 'data.data');
    }

    public function test_user_can_view_all_payments(): void
    {
        $orderOne = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        $orderTwo = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        Payment::factory()->create(['order_id' => $orderOne->id]);
        Payment::factory()->create(['order_id' => $orderTwo->id]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/v1/payments');

        $response->assertOk()->assertJsonCount(2, 'data.data');
    }
}

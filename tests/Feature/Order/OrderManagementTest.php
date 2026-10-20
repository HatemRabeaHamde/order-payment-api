<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
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

    public function test_user_can_create_order_with_calculated_total(): void
    {
        $payload = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'price' => 10.50],
                ['product_name' => 'Gadget', 'quantity' => 1, 'price' => 25],
            ],
        ];

        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/orders', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.total', 46)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'john@example.com',
            'total' => 46,
        ]);
    }

    public function test_order_creation_requires_at_least_one_item(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/orders', [
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'items' => [],
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);
    }

    public function test_order_creation_validates_item_fields(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/orders', [
                'customer_name' => 'John Doe',
                'customer_email' => 'not-an-email',
                'items' => [
                    ['product_name' => '', 'quantity' => 0, 'price' => -5],
                ],
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'customer_email',
            'items.0.product_name',
            'items.0.quantity',
            'items.0.price',
        ]);
    }

    public function test_user_can_view_paginated_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/v1/orders');

        $response->assertOk()->assertJsonCount(3, 'data.data');
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Order::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => OrderStatus::Pending]);
        Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/orders?status=confirmed');

        $response->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_filtering_orders_by_invalid_status_returns_validation_error(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/orders?status=not-a-status');

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_update_order_details(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/orders/{$order->id}", [
                'customer_name' => 'Updated Name',
            ]);

        $response->assertOk()->assertJsonPath('data.customer_name', 'Updated Name');
    }

    public function test_user_can_confirm_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => OrderStatus::Pending]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/orders/{$order->id}", ['status' => 'confirmed']);

        $response->assertOk()->assertJsonPath('data.status', 'confirmed');
    }

    public function test_order_without_payments_can_be_deleted(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_order_with_payments_cannot_be_deleted(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(409);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}

<?php

namespace Tests\Unit\Services;

use App\DTOs\CreateOrderData;
use App\DTOs\OrderItemData;
use App\Exceptions\OrderHasPaymentsException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_calculates_total_from_items(): void
    {
        $user = User::factory()->create();

        $data = new CreateOrderData(
            userId: $user->id,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            items: [
                new OrderItemData('Widget', 2, 10.50),
                new OrderItemData('Gadget', 1, 25.00),
            ],
        );

        $order = $this->app->make(OrderService::class)->create($data);

        $this->assertEquals(46.00, (float) $order->total);
        $this->assertCount(2, $order->items);
    }

    public function test_delete_throws_when_order_has_payments(): void
    {
        $order = Order::factory()->confirmed()->create();
        Payment::factory()->create(['order_id' => $order->id]);

        $this->expectException(OrderHasPaymentsException::class);

        $this->app->make(OrderService::class)->delete($order->fresh());
    }

    public function test_delete_removes_order_without_payments(): void
    {
        $order = Order::factory()->create();

        $this->app->make(OrderService::class)->delete($order);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}

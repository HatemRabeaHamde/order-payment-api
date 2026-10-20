<?php

namespace App\Services;

use App\DTOs\CreateOrderData;
use App\DTOs\OrderItemData;
use App\DTOs\UpdateOrderData;
use App\Enums\OrderStatus;
use App\Exceptions\OrderHasPaymentsException;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function list(?OrderStatus $status, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function create(CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::query()->create([
                'user_id' => $data->userId,
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'status' => OrderStatus::Pending,
                'total' => 0,
            ]);

            $total = $this->syncItems($order, $data->items);

            $order->update(['total' => $total]);

            return $order->load('items', 'payments');
        });
    }

    public function update(Order $order, UpdateOrderData $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->fill(array_filter([
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'status' => $data->status,
            ], fn ($value) => $value !== null));

            if ($data->items !== null) {
                $order->items()->delete();
                $total = $this->syncItems($order, $data->items);
                $order->total = $total;
            }

            $order->save();

            return $order->load('items', 'payments');
        });
    }

    public function delete(Order $order): void
    {
        if ($order->hasPayments()) {
            throw new OrderHasPaymentsException;
        }

        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });
    }

    /**
     * @param  array<int, OrderItemData>  $items
     */
    private function syncItems(Order $order, array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $order->items()->create([
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal(),
            ]);

            $total += $item->subtotal();
        }

        return round($total, 2);
    }
}

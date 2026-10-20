<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateOrderData;
use App\DTOs\UpdateOrderData;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $request->filled('status')
            ? OrderStatus::from($request->string('status')->toString())
            : null;

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $orders = $this->orderService->list($status, $perPage);

        return $this->success(data: OrderResource::collection($orders)->response()->getData(true));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create(
            CreateOrderData::fromRequest($request->validated(), Auth::id())
        );

        return $this->success(
            data: new OrderResource($order),
            message: 'Order created successfully.',
            status: 201,
        );
    }

    public function show(Order $order): JsonResponse
    {
        return $this->success(data: new OrderResource($order->load('items', 'payments')));
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->update(
            $order,
            UpdateOrderData::fromRequest($request->validated())
        );

        return $this->success(data: new OrderResource($order), message: 'Order updated successfully.');
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->orderService->delete($order);

        return $this->success(message: 'Order deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProcessPaymentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $payments = $this->paymentService->listAll($perPage);

        return $this->success(data: PaymentResource::collection($payments)->response()->getData(true));
    }

    public function indexForOrder(Request $request, Order $order): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $payments = $this->paymentService->listForOrder($order, $perPage);

        return $this->success(data: PaymentResource::collection($payments)->response()->getData(true));
    }

    public function store(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $payment = $this->paymentService->process(
            $order,
            ProcessPaymentData::fromRequest($request->validated(), $order->id)
        );

        return $this->success(
            data: new PaymentResource($payment),
            message: 'Payment processed.',
            status: 201,
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Modules\Orders\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(OrderIndexRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated('per_page') ?? 10);

        $orders = $this->orderService->getUserOrders(
            $request->user(),
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => OrderResource::collection($orders)->resolve($request),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createFromCart(
                $request->user(),
                $request->validated()
            );

            return $this->createdResponse(
                'Order created successfully.',
                new OrderResource($order)
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse(
                $exception->getMessage(),
                422
            );
        }
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $userOrder = $this->orderService->getUserOrder(
            $request->user(),
            $order
        );

        return $this->successResponse(
            'Order details retrieved successfully.',
            new OrderResource($userOrder)
        );
    }

    public function cancel(Request $request, int $order): JsonResponse
    {
        try {
            $cancelledOrder = $this->orderService->cancelOrder(
                $request->user(),
                $order
            );

            return $this->successResponse(
                'Order cancelled successfully.',
                new OrderResource($cancelledOrder)
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse(
                $exception->getMessage(),
                422
            );
        }
    }
}

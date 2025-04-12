<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderListResource;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Types\OrderStatusEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $orders = $this->orderService->getOrders();
        
        return response()->json([
            'status' => 'success',
            'data' => OrderListResource::collection($orders)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => new OrderListResource($order)
            ], 201);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id): JsonResponse
    {
        $order = $this->orderService->getOrderByOrderId($id);
        return response()->json([
            'status' => 'success',
            'data' => new OrderResource($order)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, String $id): JsonResponse
    {
        $order = $this->orderService->getOrderByOrderId($id);
        try {
            $order = $this->orderService->updateOrder($order, $request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order updated successfully',
                'data' => new OrderListResource($order)
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Update the specified resource status in storage.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, string $id)
    {
        try {
            $order = $this->orderService->getOrderByOrderId($id);
            $statusString = $request->validated('status');
            
            try {
                $status = OrderStatusEnum::from($statusString);
            } catch (\ValueError $e) {
                // Get allowed values from the enum
                $allowedValues = implode(', ', OrderStatusEnum::values());
                
                return response()->json([
                    'status' => 'error',
                    'message' => "Invalid status value. Allowed values are: $allowedValues"
                ], 422);
            }

            $order = $this->orderService->updateOrderStatus($order, $status);
            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => new OrderResource($order)
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderByOrderId($id);
            $this->authorize('delete', $order);
            
            $this->orderService->deleteOrder($order);
            return response()->json([
                'status' => 'success',
                'message' => 'Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    protected function handleError(\Exception $e, $statusCode = 500): JsonResponse
    {
        if ($e instanceof ModelNotFoundException) {
            $statusCode = 404;
            $error = 'Order not found.';
        } elseif ($e instanceof ValidationException) {
            $statusCode = 422;
            $error = $e->errors();
        } else {
            $error = $e;
        }

        return response()->json([
            'error' => $error
        ], $statusCode);
    }
}

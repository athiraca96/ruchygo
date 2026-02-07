<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateOrderItemStatusRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = auth()->id();

        $query = Order::whereHas('items', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })
            ->with(['user', 'items' => function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId)->with('product');
            }]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $vendorId = auth()->id();

        // Check if vendor has items in this order
        if (!$order->items()->where('vendor_id', $vendorId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->load(['user', 'items' => function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId)->with('product');
        }]);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    public function updateItemStatus(UpdateOrderItemStatusRequest $request, OrderItem $orderItem): JsonResponse
    {
        if ($orderItem->vendor_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
            ], 404);
        }

        if (!$orderItem->canUpdateStatus()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update status for this order item',
            ], 422);
        }

        $orderItem->update(['status' => $request->status]);

        // Update overall order status if all items have same status
        $this->updateOrderStatus($orderItem->order);

        return response()->json([
            'success' => true,
            'message' => 'Order item status updated successfully',
            'data' => new OrderItemResource($orderItem->load('product')),
        ]);
    }

    protected function updateOrderStatus(Order $order): void
    {
        $statuses = $order->items()->pluck('status')->unique();

        if ($statuses->count() === 1) {
            $status = $statuses->first();
            if (in_array($status, ['delivered', 'cancelled'])) {
                $order->update(['status' => $status]);
            } elseif ($status === 'shipped') {
                $order->update(['status' => 'shipped']);
            } elseif ($status === 'processing') {
                $order->update(['status' => 'processing']);
            } elseif ($status === 'confirmed') {
                $order->update(['status' => 'confirmed']);
            }
        } elseif ($statuses->contains('processing') || $statuses->contains('shipped')) {
            $order->update(['status' => 'processing']);
        }
    }
}

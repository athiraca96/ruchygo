<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::forUser(auth()->id())
            ->with(['items.product.images']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

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
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->load(['items.product.images', 'items.vendor']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    public function cancel(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled',
            ], 422);
        }

        // Restore stock
        foreach ($order->items as $item) {
            $item->product->increment('stock_quantity', $item->quantity);
            $item->update(['status' => 'cancelled']);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => new OrderResource($order->load(['items.product'])),
        ]);
    }
}

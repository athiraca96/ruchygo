<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReturnRequestRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;

class ReturnRequestController extends Controller
{
    public function __construct(
        protected PlatformSettingService $settingService
    ) {}

    public function index(): JsonResponse
    {
        $returnRequests = ReturnRequest::where('user_id', auth()->id())
            ->with(['order', 'orderItem.product'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ReturnRequestResource::collection($returnRequests),
            'meta' => [
                'current_page' => $returnRequests->currentPage(),
                'last_page' => $returnRequests->lastPage(),
                'per_page' => $returnRequests->perPage(),
                'total' => $returnRequests->total(),
            ],
        ]);
    }

    public function store(StoreReturnRequestRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Verify user owns this order
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Verify order can be returned
        if (!$order->canBeReturned()) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be returned or replaced',
            ], 422);
        }

        // Verify order item belongs to the order
        $orderItem = OrderItem::where('id', $request->order_item_id)
            ->where('order_id', $request->order_id)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
            ], 404);
        }

        // Check if within return/replacement window
        $windowDays = $request->type === 'return'
            ? $this->settingService->getReturnWindowDays()
            : $this->settingService->getReplacementWindowDays();

        $deliveredAt = $order->updated_at; // Assuming updated_at reflects delivery time
        if ($deliveredAt->addDays($windowDays)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => "The {$request->type} window of {$windowDays} days has passed",
            ], 422);
        }

        // Check if already requested for this item
        $existingRequest = ReturnRequest::where('order_item_id', $orderItem->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A return/replacement request already exists for this item',
            ], 422);
        }

        $returnRequest = ReturnRequest::create([
            'user_id' => $user->id,
            'order_id' => $request->order_id,
            'order_item_id' => $request->order_item_id,
            'type' => $request->type,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->type) . ' request submitted successfully',
            'data' => new ReturnRequestResource($returnRequest->load(['order', 'orderItem.product'])),
        ], 201);
    }
}

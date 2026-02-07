<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class ComplaintController extends Controller
{
    public function index(): JsonResponse
    {
        $complaints = Complaint::where('user_id', auth()->id())
            ->with(['order', 'product'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ComplaintResource::collection($complaints),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
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

        // If product_id is provided, verify it's in the order
        if ($request->product_id) {
            $hasProduct = $order->items()->where('product_id', $request->product_id)->exists();
            if (!$hasProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product is not part of the specified order',
                ], 422);
            }
        }

        $complaint = Complaint::create([
            'user_id' => $user->id,
            'order_id' => $request->order_id,
            'product_id' => $request->product_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Complaint submitted successfully',
            'data' => new ComplaintResource($complaint->load(['order', 'product'])),
        ], 201);
    }
}

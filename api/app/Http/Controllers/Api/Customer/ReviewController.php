<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $reviews = Review::where('user_id', auth()->id())
            ->with(['product', 'order'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(StoreReviewRequest $request): JsonResponse
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

        // Verify order is delivered
        if ($order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'You can only review products from delivered orders',
            ], 422);
        }

        // Verify product is in this order
        $hasProduct = $order->items()->where('product_id', $request->product_id)->exists();
        if (!$hasProduct) {
            return response()->json([
                'success' => false,
                'message' => 'This product is not part of the specified order',
            ], 422);
        }

        // Check if already reviewed
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('order_id', $request->order_id)
            ->exists();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product for this order',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => false, // Admin needs to approve
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully. It will be visible after approval.',
            'data' => new ReviewResource($review->load('product')),
        ], 201);
    }
}

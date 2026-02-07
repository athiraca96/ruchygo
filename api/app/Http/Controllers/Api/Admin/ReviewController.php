<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'product', 'order']);

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->latest()->paginate(20);

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

    public function approve(Review $review): JsonResponse
    {
        $review->update(['is_approved' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully',
            'data' => new ReviewResource($review->load(['user', 'product'])),
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}

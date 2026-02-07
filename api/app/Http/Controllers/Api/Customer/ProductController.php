<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::active()
            ->with(['subcategories' => function ($q) {
                $q->active()->ordered();
            }])
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::available()
            ->with(['category', 'subcategory', 'images', 'vendor'])
            ->withCount(['reviews' => function ($q) {
                $q->where('is_approved', true);
            }]);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by subcategory
        if ($request->has('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('discount_price', '>=', $request->min_price)
                    ->orWhere(function ($q) use ($request) {
                        $q->whereNull('discount_price')
                            ->where('price', '>=', $request->min_price);
                    });
            });
        }

        if ($request->has('max_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('discount_price', '<=', $request->max_price)
                    ->orWhere(function ($q) use ($request) {
                        $q->whereNull('discount_price')
                            ->where('price', '<=', $request->max_price);
                    });
            });
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->whereHas('reviews', function ($q) use ($request) {
                $q->where('is_approved', true)
                    ->havingRaw('AVG(rating) >= ?', [$request->min_rating]);
            });
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'price') {
            $query->orderByRaw('COALESCE(discount_price, price) ' . $sortOrder);
        } elseif ($sortBy === 'rating') {
            $query->withAvg(['reviews' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
                ->orderBy('reviews_avg_rating', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->available()
            ->with(['category', 'subcategory', 'images', 'vendor'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function reviews(string $slug, Request $request): JsonResponse
    {
        $product = Product::where('slug', $slug)->available()->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $reviews = Review::where('product_id', $product->id)
            ->approved()
            ->with('user')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'average_rating' => $product->getAverageRating(),
                'total_reviews' => $reviews->total(),
                'reviews' => ReviewResource::collection($reviews),
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }
}

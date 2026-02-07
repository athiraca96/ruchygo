<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductActionRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['vendor', 'category', 'subcategory', 'images']);

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->latest()->paginate(20);

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

    public function show(Product $product): JsonResponse
    {
        $product->load(['vendor', 'category', 'subcategory', 'images']);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function approve(ProductActionRequest $request, Product $product): JsonResponse
    {
        $product->update([
            'is_approved' => true,
            'admin_remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product approved successfully',
            'data' => new ProductResource($product->load(['vendor', 'category', 'subcategory', 'images'])),
        ]);
    }

    public function reject(ProductActionRequest $request, Product $product): JsonResponse
    {
        $product->update([
            'is_approved' => false,
            'admin_remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product rejected',
            'data' => new ProductResource($product->load(['vendor', 'category', 'subcategory', 'images'])),
        ]);
    }
}

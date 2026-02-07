<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::forVendor(auth()->id())
            ->with(['category', 'subcategory', 'images']);

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['vendor_id'] = auth()->id();
        $data['is_approved'] = false;

        unset($data['images']);

        $product = Product::create($data);

        // Handle images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully. Pending admin approval.',
            'data' => new ProductResource($product->load(['category', 'subcategory', 'images'])),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        if ($product->vendor_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->load(['category', 'subcategory', 'images']);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        if ($product->vendor_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->load(['category', 'subcategory', 'images'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->vendor_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Delete images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        if ($product->vendor_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $request->validate([
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['image', 'max:2048'],
        ]);

        $currentCount = $product->images()->count();
        $maxOrder = $product->images()->max('sort_order') ?? -1;

        foreach ($request->file('images') as $index => $image) {
            if ($currentCount + $index >= 5) {
                break;
            }

            $path = $image->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $currentCount === 0 && $index === 0,
                'sort_order' => $maxOrder + $index + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => new ProductResource($product->load(['category', 'subcategory', 'images'])),
        ]);
    }

    public function deleteImage(Product $product, ProductImage $image): JsonResponse
    {
        if ($product->vendor_id !== auth()->id() || $image->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        // If deleted image was primary, make first remaining image primary
        if ($image->is_primary) {
            $firstImage = $product->images()->orderBy('sort_order')->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);
    }
}

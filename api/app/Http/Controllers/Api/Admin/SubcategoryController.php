<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubcategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subcategory::with('category')->withCount('products')->ordered();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $subcategories = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => SubcategoryResource::collection($subcategories),
            'meta' => [
                'current_page' => $subcategories->currentPage(),
                'last_page' => $subcategories->lastPage(),
                'per_page' => $subcategories->perPage(),
                'total' => $subcategories->total(),
            ],
        ]);
    }

    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        }

        $subcategory = Subcategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Subcategory created successfully',
            'data' => new SubcategoryResource($subcategory->load('category')),
        ], 201);
    }

    public function show(Subcategory $subcategory): JsonResponse
    {
        $subcategory->load('category')->loadCount('products');

        return response()->json([
            'success' => true,
            'data' => new SubcategoryResource($subcategory),
        ]);
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($subcategory->image) {
                Storage::disk('public')->delete($subcategory->image);
            }
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        }

        $subcategory->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Subcategory updated successfully',
            'data' => new SubcategoryResource($subcategory->load('category')),
        ]);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        if ($subcategory->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subcategory with existing products',
            ], 422);
        }

        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        $subcategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subcategory deleted successfully',
        ]);
    }
}

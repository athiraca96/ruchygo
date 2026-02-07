<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $productIds = Product::forVendor(auth()->id())->pluck('id');

        $query = Complaint::whereIn('product_id', $productIds)
            ->with(['user', 'order', 'product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $complaints = $query->latest()->paginate(20);

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
}

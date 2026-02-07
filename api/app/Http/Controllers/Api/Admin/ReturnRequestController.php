<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReturnRequestRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ReturnRequest::with(['user', 'order', 'orderItem.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $returnRequests = $query->latest()->paginate(20);

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

    public function show(ReturnRequest $returnRequest): JsonResponse
    {
        $returnRequest->load(['user', 'order', 'orderItem.product']);

        return response()->json([
            'success' => true,
            'data' => new ReturnRequestResource($returnRequest),
        ]);
    }

    public function update(UpdateReturnRequestRequest $request, ReturnRequest $returnRequest): JsonResponse
    {
        $returnRequest->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Return request updated successfully',
            'data' => new ReturnRequestResource($returnRequest->load(['user', 'order', 'orderItem.product'])),
        ]);
    }
}

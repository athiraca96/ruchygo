<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Complaint::with(['user', 'order', 'product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
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

    public function show(Complaint $complaint): JsonResponse
    {
        $complaint->load(['user', 'order.items', 'product']);

        return response()->json([
            'success' => true,
            'data' => new ComplaintResource($complaint),
        ]);
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint): JsonResponse
    {
        $complaint->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Complaint updated successfully',
            'data' => new ComplaintResource($complaint->load(['user', 'order', 'product'])),
        ]);
    }
}

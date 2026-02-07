<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Http\Requests\Admin\VendorActionRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::vendor()->with('vendorProfile');

        if ($request->has('status') && $request->status) {
            $query->whereHas('vendorProfile', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('vendorProfile', function ($q) use ($search) {
                        $q->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        $vendors = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($vendors),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
            ],
        ]);
    }

    public function show(User $vendor): JsonResponse
    {
        if ($vendor->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a vendor',
            ], 404);
        }

        $vendor->load('vendorProfile');

        return response()->json([
            'success' => true,
            'data' => new UserResource($vendor),
        ]);
    }

    public function update(UpdateVendorRequest $request, User $vendor): JsonResponse
    {
        if ($vendor->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a vendor',
            ], 404);
        }

        $vendor->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully',
            'data' => new UserResource($vendor->load('vendorProfile')),
        ]);
    }

    public function approve(VendorActionRequest $request, User $vendor): JsonResponse
    {
        if ($vendor->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a vendor',
            ], 404);
        }

        if (!$vendor->vendorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor has not completed onboarding',
            ], 422);
        }

        $vendor->vendorProfile->update([
            'status' => 'approved',
            'admin_remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor approved successfully',
            'data' => new UserResource($vendor->load('vendorProfile')),
        ]);
    }

    public function reject(VendorActionRequest $request, User $vendor): JsonResponse
    {
        if ($vendor->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a vendor',
            ], 404);
        }

        if (!$vendor->vendorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor has not completed onboarding',
            ], 422);
        }

        $vendor->vendorProfile->update([
            'status' => 'rejected',
            'admin_remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor rejected',
            'data' => new UserResource($vendor->load('vendorProfile')),
        ]);
    }
}

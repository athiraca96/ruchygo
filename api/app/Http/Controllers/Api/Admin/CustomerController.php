<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::customer();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(User $customer): JsonResponse
    {
        if ($customer->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a customer',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($customer),
        ]);
    }

    public function toggleStatus(User $customer): JsonResponse
    {
        if ($customer->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a customer',
            ], 404);
        }

        $customer->update(['is_active' => !$customer->is_active]);

        return response()->json([
            'success' => true,
            'message' => $customer->is_active ? 'Customer activated' : 'Customer deactivated',
            'data' => new UserResource($customer),
        ]);
    }
}

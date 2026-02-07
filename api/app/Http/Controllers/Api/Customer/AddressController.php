<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreAddressRequest;
use App\Http\Requests\Customer\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = auth()->user()->addresses()->orderBy('is_default', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // If this is first address or marked as default, unset other defaults
        if ($request->boolean('is_default') || !auth()->user()->addresses()->exists()) {
            auth()->user()->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address = Address::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully',
            'data' => new AddressResource($address),
        ], 201);
    }

    public function show(Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AddressResource($address),
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        $data = $request->validated();

        if ($request->boolean('is_default')) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => new AddressResource($address),
        ]);
    }

    public function destroy(Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If deleted address was default, make another one default
        if ($wasDefault) {
            $firstAddress = auth()->user()->addresses()->first();
            if ($firstAddress) {
                $firstAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully',
        ]);
    }
}

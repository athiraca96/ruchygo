<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\OnboardingRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\VendorProfileResource;
use App\Models\VendorProfile;
use Illuminate\Http\JsonResponse;

class OnboardingController extends Controller
{
    public function store(OnboardingRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->vendorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor profile already exists',
            ], 422);
        }

        $data = $request->validated();

        if ($request->hasFile('id_proof_document')) {
            $data['id_proof_document'] = $request->file('id_proof_document')
                ->store('vendor-documents', 'public');
        }

        $data['user_id'] = $user->id;
        $data['status'] = 'pending';

        $vendorProfile = VendorProfile::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Vendor onboarding completed. Your application is pending review.',
            'data' => new VendorProfileResource($vendorProfile),
        ], 201);
    }

    public function status(): JsonResponse
    {
        $user = auth()->user();
        $vendorProfile = $user->vendorProfile;

        if (!$vendorProfile) {
            return response()->json([
                'success' => true,
                'data' => [
                    'onboarding_completed' => false,
                    'status' => null,
                    'message' => 'Please complete vendor onboarding',
                ],
            ]);
        }

        $message = match ($vendorProfile->status) {
            'pending' => 'Your application is pending review',
            'approved' => 'Your vendor account is approved',
            'rejected' => 'Your application was rejected',
            default => 'Unknown status',
        };

        return response()->json([
            'success' => true,
            'data' => [
                'onboarding_completed' => true,
                'status' => $vendorProfile->status,
                'message' => $message,
                'admin_remarks' => $vendorProfile->admin_remarks,
                'profile' => new VendorProfileResource($vendorProfile),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class OtpAuthController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->sendOtp($request->phone);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'expires_in' => $result['expires_in'],
            ],
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $role = $request->input('role', 'customer');
        $result = $this->otpService->verifyOtp($request->phone, $request->otp, $role);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'vendor_status' => $result['vendor_status'],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}

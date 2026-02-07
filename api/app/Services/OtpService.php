<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected TwilioService $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    public function sendOtp(string $phone): array
    {
        $result = $this->twilioService->sendVerification($phone);

        if (!$result['success']) {
            Log::error("Failed to send OTP to {$phone}: " . $result['message']);
            return [
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Verification code sent successfully',
            'expires_in' => config('platform.otp.expiry_minutes', 10) * 60,
        ];
    }

    public function verifyOtp(string $phone, string $otp, string $role = 'customer'): array
    {
        $result = $this->twilioService->checkVerification($phone, $otp);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'],
            ];
        }

        // Find or create user
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $user = User::create([
                'phone' => $phone,
                'role' => $role,
                'phone_verified_at' => now(),
            ]);
        } else {
            if (!$user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        // Check if user is active
        if (!$user->is_active) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated',
            ];
        }

        // For vendor role, check if they are approved
        $vendorStatus = null;
        if ($user->role === 'vendor') {
            $vendorProfile = $user->vendorProfile;
            if (!$vendorProfile) {
                $vendorStatus = 'onboarding_required';
            } else {
                $vendorStatus = $vendorProfile->status;
            }
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
            'user' => $user,
            'token' => $token,
            'vendor_status' => $vendorStatus,
        ];
    }
}

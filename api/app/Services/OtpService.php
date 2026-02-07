<?php

namespace App\Services;

use App\Models\OtpVerification;
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
        // Invalidate any existing OTPs for this phone
        OtpVerification::where('phone', $phone)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Generate new OTP
        $otp = $this->generateOtp();
        $expiryMinutes = config('platform.otp.expiry_minutes', 10);

        // Store OTP
        OtpVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        // Send SMS
        $message = "Your RuchyGo verification code is: {$otp}. Valid for {$expiryMinutes} minutes.";
        $sent = $this->twilioService->sendSms($phone, $message);

        if (!$sent) {
            Log::error("Failed to send OTP to {$phone}");
        }

        // For development, log the OTP
        if (config('app.debug')) {
            Log::info("OTP for {$phone}: {$otp}");
        }

        return [
            'success' => true,
            'message' => 'OTP sent successfully',
            'expires_in' => $expiryMinutes * 60, // seconds
        ];
    }

    public function verifyOtp(string $phone, string $otp, string $role = 'customer'): array
    {
        $verification = OtpVerification::where('phone', $phone)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ];
        }

        // Mark OTP as used
        $verification->update(['is_used' => true]);

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

    protected function generateOtp(): string
    {
        $length = config('platform.otp.length', 6);
        return str_pad((string) random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}

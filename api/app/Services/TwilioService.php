<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected ?Client $client = null;
    protected ?string $verifySid = null;

    public function __construct()
    {
        $sid = config('platform.twilio.sid');
        $token = config('platform.twilio.auth_token');
        $this->verifySid = config('platform.twilio.verify_sid');

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }
    }

    public function sendVerification(string $phone, string $channel = 'sms'): array
    {
        $formattedPhone = $this->formatE164($phone);

        if (!$this->client || !$this->verifySid) {
            Log::warning('Twilio Verify not configured. Verification would be sent to: ' . $formattedPhone);
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Verification sent (dev mode)',
            ];
        }

        try {
            $verification = $this->client->verify->v2
                ->services($this->verifySid)
                ->verifications
                ->create($formattedPhone, $channel);

            Log::info('Verification sent to: ' . $formattedPhone . ' Status: ' . $verification->status);

            return [
                'success' => true,
                'status' => $verification->status,
                'message' => 'Verification code sent successfully',
            ];
        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error('Failed to send verification: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')');

            $message = match ($e->getCode()) {
                60203 => 'Max send attempts reached. Please try again later.',
                60205 => 'SMS is not supported for this region.',
                default => 'Failed to send verification code. Please try again.',
            };

            return [
                'success' => false,
                'status' => 'failed',
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send verification: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to send verification code. Please try again.',
            ];
        }
    }

    public function checkVerification(string $phone, string $code): array
    {
        $formattedPhone = $this->formatE164($phone);

        if (!$this->client || !$this->verifySid) {
            Log::warning('Twilio Verify not configured. Code check for: ' . $formattedPhone);
            // In dev mode, accept any 6-digit code
            return [
                'success' => true,
                'status' => 'approved',
                'message' => 'Verification approved (dev mode)',
            ];
        }

        try {
            $verificationCheck = $this->client->verify->v2
                ->services($this->verifySid)
                ->verificationChecks
                ->create([
                    'to' => $formattedPhone,
                    'code' => $code,
                ]);

            Log::info('Verification check for: ' . $formattedPhone . ' Status: ' . $verificationCheck->status);

            $approved = $verificationCheck->status === 'approved';

            return [
                'success' => $approved,
                'status' => $verificationCheck->status,
                'message' => $approved ? 'Verification successful' : 'Invalid or expired code',
            ];
        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error('Failed to check verification: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')');

            // Provide user-friendly error messages based on Twilio error codes
            $message = match ($e->getCode()) {
                60200 => 'Invalid verification code',
                60202 => 'Max verification attempts reached. Please request a new code.',
                60203 => 'Max send attempts reached. Please try again later.',
                60212 => 'Verification code expired. Please request a new code.',
                default => 'Verification failed. Please try again.',
            };

            return [
                'success' => false,
                'status' => 'failed',
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check verification: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Verification failed. Please try again.',
            ];
        }
    }

    protected function formatE164(string $phone, ?string $countryCode = null): string
    {
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $countryCode = $countryCode ?? config('platform.otp.default_country_code', '+91');
        $phone = ltrim($phone, '0');

        return $countryCode . $phone;
    }
}

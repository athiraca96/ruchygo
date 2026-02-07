<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected ?Client $client = null;
    protected string $from;

    public function __construct()
    {
        $sid = config('platform.twilio.sid');
        $token = config('platform.twilio.auth_token');
        $this->from = config('platform.twilio.phone_number');

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }
    }

    public function sendSms(string $to, string $message): bool
    {
        if (!$this->client) {
            Log::warning('Twilio not configured. SMS would be sent to: ' . $to . ' Message: ' . $message);
            return true;
        }

        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            Log::info('SMS sent successfully to: ' . $to);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS: ' . $e->getMessage());
            return false;
        }
    }
}

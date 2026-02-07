<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    */

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => 6,
        'expiry_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Platform Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'platform_fee_percentage' => 5,
        'shipping_fee' => 50,
        'free_shipping_threshold' => 500,
        'return_window_days' => 7,
        'replacement_window_days' => 7,
    ],

];

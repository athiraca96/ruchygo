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
        'verify_sid' => env('TWILIO_VERIFY_SID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => 6,
        'expiry_minutes' => 10,
        'default_country_code' => env('DEFAULT_COUNTRY_CODE', '+91'),
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

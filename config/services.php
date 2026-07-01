<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ─── SMS ───────────────────────────────────────────

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'), // log | unimatrix
    ],

    'unimatrix' => [
        'api_url' => env('UNIMATRIX_API_URL', 'https://api.unimatrix.cm/sms/1/text/query'),
        'access_key_id' => env('UNIMATRIX_ACCESS_KEY_ID'),
        'secret_key' => env('UNIMATRIX_SECRET_KEY'),
        'sender' => env('UNIMATRIX_SENDER', 'FanPay'),
    ],

    // ─── Paiements (Mobile Money) ───────────────────────

    'campay' => [
        'api_url' => env('CAMPAY_API_URL', 'https://demo.campay.net/api'),
        'app_username' => env('CAMPAY_APP_USERNAME'),
        'app_password' => env('CAMPAY_APP_PASSWORD'),
    ],

    'notchpay' => [
        'api_url' => env('NOTCHPAY_API_URL', 'https://api.notchpay.co'),
        'api_key' => env('NOTCHPAY_API_KEY'),
        'encryption_key' => env('NOTCHPAY_ENCRYPTION_KEY'),
    ],

];

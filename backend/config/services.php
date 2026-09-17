<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    // External reservation system ("köprü"). Secrets only via .env — never shown in the panel.
    'reservation' => [
        'provider' => env('RESERVATION_PROVIDER'), // empty = integration disabled (NullProvider); "fake" for demo/tests
        'base_url' => env('RESERVATION_API_BASE_URL'),
        'key' => env('RESERVATION_API_KEY'),
        'secret' => env('RESERVATION_API_SECRET'),
        'webhook_secret' => env('RESERVATION_WEBHOOK_SECRET'),
        'timeout' => (int) env('RESERVATION_API_TIMEOUT', 10),
        'sync_days' => (int) env('RESERVATION_SYNC_DAYS', 30),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL'),
        // Base of the customer menu link printed in table QR codes (the token is appended as ?t=).
        'qr_menu_url' => env('QR_MENU_URL', env('FRONTEND_URL')),
    ],

];

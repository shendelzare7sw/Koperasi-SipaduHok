<?php

return [

    'payment_gateway' => env('PAYMENT_GATEWAY', 'placeholder'),

    'paywuz' => [
        'base_url' => env('PAYWUZ_BASE_URL', 'https://api.paywuz.id/v1'),
        'sandbox_api_key' => env('PAYWUZ_SANDBOX_API_KEY'),
        'production_api_key' => env('PAYWUZ_PRODUCTION_API_KEY'),
        'environment' => env('PAYWUZ_ENVIRONMENT', 'sandbox'),
        'expiry_minutes' => (int) env('PAYWUZ_EXPIRY_MINUTES', 720),
    ],

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

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'hostname' => env('TURNSTILE_HOSTNAME'),
    ],

];

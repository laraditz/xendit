<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Xendit API Key
    |--------------------------------------------------------------------------
    |
    | Your Xendit secret API key. Get it from Xendit Dashboard.
    | https://dashboard.xendit.co/settings/developers#api-keys
    |
    */
    'api_key' => env('XENDIT_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Xendit Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for Xendit API. Default is production URL.
    | For testing, you can use: https://api.xendit.co
    |
    */
    'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use for payments. Supported: IDR, PHP, THB, VND, MYR
    |
    */
    'default_currency' => env('XENDIT_CURRENCY', 'MYR'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The Xendit API version to use. Required for v3 endpoints.
    |
    */
    'api_version' => env('XENDIT_API_VERSION', '2024-11-11'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds
    |
    */
    'timeout' => env('XENDIT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Your webhook verification token from Xendit Dashboard.
    | Used to verify incoming webhook requests.
    |
    */
    'webhook_secret' => env('XENDIT_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | The URL where Xendit will send webhook notifications.
    |
    */
    'webhook_url' => env('XENDIT_WEBHOOK_URL', '/xendit/webhook'),
];
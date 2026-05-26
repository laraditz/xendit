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
    | API Versions
    |--------------------------------------------------------------------------
    |
    | Per-service API version headers. Add a key for any service that requires
    | a specific api-version header. Omit the key to send no header (the default).
    | Set a key to null to suppress the header even if the service has a default.
    |
    | Available keys: payment_request, payment, payment_token, session, customer,
    |                 refund, payment_link, transaction
    |
    */
    'api_versions' => [
        'payment_request' => '2024-11-11',
        'payment' => '2024-11-11',
        'payment_token' => '2024-11-11',
        'customer' => '2020-10-31',
        // 'session'         => '2024-05-01',
        //
        // To suppress the header for a service that has a $defaultApiVersion,
        // the key must be PRESENT with value null — commenting the line out does
        // NOT suppress; it falls through to the service default.
        // 'payment' => null,
    ],

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
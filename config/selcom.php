<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Selcom Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SELCOM mobile money payment gateway integration.
    | This handles both collections and disbursements.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Vendor Credentials
    |--------------------------------------------------------------------------
    */

    'vendor_id' => env('SELCOM_VENDOR_ID'),
    'vendor_pin' => env('SELCOM_VENDOR_PIN'),
    'api_key' => env('SELCOM_API_KEY'),
    'api_secret' => env('SELCOM_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */

    'base_url' => env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'),
    'timeout' => env('SELCOM_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    'payment_expiry' => env('SELCOM_PAYMENT_EXPIRY', 14400), // 10 days in minutes
    'default_currency' => env('SELCOM_DEFAULT_CURRENCY', 'TZS'),

    // Minimum and maximum disbursement amounts (in TZS)
    'min_disbursement_amount' => env('SELCOM_MIN_DISBURSEMENT', 1000),
    'max_disbursement_amount' => env('SELCOM_MAX_DISBURSEMENT', 5000000),

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    */

    'callback_url' => env('SELCOM_CALLBACK_URL', env('APP_URL') . '/api/selcom/callback'),
    'cancel_url' => env('SELCOM_CANCEL_URL', env('APP_URL')),
    'webhook_secret' => env('SELCOM_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Debug & Logging
    |--------------------------------------------------------------------------
    */

    'debug' => env('SELCOM_DEBUG', false),
    'log_requests' => env('SELCOM_LOG_REQUESTS', true),
    'log_responses' => env('SELCOM_LOG_RESPONSES', true),
    'sanitize_logs' => env('SELCOM_SANITIZE_LOGS', true),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'retry_failed_requests' => env('SELCOM_RETRY_FAILED', true),
    'max_retries' => env('SELCOM_MAX_RETRIES', 3),
    'retry_delay_seconds' => env('SELCOM_RETRY_DELAY', 60),

    /*
    |--------------------------------------------------------------------------
    | Supported Wallet Types
    |--------------------------------------------------------------------------
    |
    | Mapping of wallet types to SELCOM utility codes
    |
    */

    'wallet_types' => [
        'vodacom' => [
            'code' => 'VMCASHIN',
            'name' => 'Vodacom M-Pesa',
            'prefixes' => ['074', '075', '076'],
        ],
        'airtel' => [
            'code' => 'AMCASHIN',
            'name' => 'Airtel Money',
            'prefixes' => ['068', '069', '078'],
        ],
        'tigo' => [
            'code' => 'TPCASHIN',
            'name' => 'Tigo Pesa',
            'prefixes' => ['071', '065', '067'],
        ],
        'halopesa' => [
            'code' => 'HPCASHIN',
            'name' => 'HaloPesa',
            'prefixes' => ['062'],
        ],
        'ttcl' => [
            'code' => 'TTCASHIN',
            'name' => 'TTCL Pesa',
            'prefixes' => ['073'],
        ],
        'ezy' => [
            'code' => 'EZCASHIN',
            'name' => 'EzyPesa',
            'prefixes' => ['077'],
        ],
        'auto' => [
            'code' => 'CASHIN',
            'name' => 'Auto Detect',
            'prefixes' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('SELCOM_RATE_LIMIT_ENABLED', true),
        'max_requests_per_minute' => env('SELCOM_RATE_LIMIT_PER_MINUTE', 60),
    ],
];

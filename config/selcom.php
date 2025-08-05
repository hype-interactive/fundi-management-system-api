<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Selcom Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'vendor_id' => env('SELCOM_VENDOR_ID', 'TILL60611001'),
    'vendor_pin' => env('SELCOM_VENDOR_PIN'),
    'api_key' => env('SELCOM_API_KEY'),
    'api_secret' => env('SELCOM_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    'payment_expiry' => env('SELCOM_PAYMENT_EXPIRY', 24 * 60 * 10), // 10 days in minutes
    'default_currency' => env('SELCOM_DEFAULT_CURRENCY', 'TZS'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Recipients (for predetermined recipient validation)
    |--------------------------------------------------------------------------
    */

    'allowed_recipients' => [
        // Add phone numbers or emails of allowed recipients
        // This can be dynamically managed through your admin interface
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */

    'endpoints' => [
        'production' => [
            'checkout' => 'https://apigw.selcommobile.com/v1/checkout/create-order-minimal',
            'wallet' => 'https://apigw.selcommobile.com/v1/checkout/wallet-payment',
            'wallet_cashin' => 'https://apigw.selcommobile.com/v1/walletcashin/process',
            'wallet_lookup' => 'https://apigw.selcommobile.com/v1/walletcashin/namelookup',
            'wallet_query' => 'https://apigw.selcommobile.com/v1/walletcashin/query',
            'selcompesa_cashin' => 'https://apigw.selcommobile.com/v1/selcompesa/cashin',
            'selcompesa_lookup' => 'https://apigw.selcommobile.com/v1/selcompesa/namelookup',
            'selcompesa_query' => 'https://apigw.selcommobile.com/v1/selcompesa/query',
            'orders' => 'https://apigw.selcommobile.com/v1/checkout/list-orders',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    */

    'callback_url' => env('SELCOM_CALLBACK_URL', 'https://mafundi.magicbuilders.co.tz/api/selcom/payment-callback'),
    'cancel_url' => env('SELCOM_CANCEL_URL', 'https://mafundi.magicbuilders.co.tz/'),

    /*
    |--------------------------------------------------------------------------
    | Debug Settings
    |--------------------------------------------------------------------------
    */

    'debug' => env('SELCOM_DEBUG', false),
    'log_requests' => env('SELCOM_LOG_REQUESTS', true),
    'log_responses' => env('SELCOM_LOG_RESPONSES', true),
];

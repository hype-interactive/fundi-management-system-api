<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | This option defines the default SMS provider that will be used by your
    | application. You may set this to any of the providers defined in the
    | "providers" array below.
    |
    | Supported: "beem", "nextsms", "monex"
    |
    */

    'default_provider' => env('SMS_DEFAULT_PROVIDER', 'beem'),

    /*
    |--------------------------------------------------------------------------
    | SMS Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many SMS providers as you wish. You may even
    | configure multiple providers for the same service to allow for failover.
    |
    */

    'providers' => [

        'beem' => [
            'api_key' => env('BEEM_API_KEY'),
            'api_secret' => env('BEEM_API_SECRET'),
            'sender_id' => env('BEEM_SENDER_ID', 'MAGICBUILD'),
            'base_url' => env('BEEM_BASE_URL', 'https://apisms.beem.africa/v1/send'),
        ],

        'nextsms' => [
            'username' => env('NEXTSMS_USERNAME'),
            'password' => env('NEXTSMS_PASSWORD'),
            'sender_id' => env('NEXTSMS_SENDER_ID', 'MAGICBUILD'),
            'base_url' => env('NEXTSMS_BASE_URL', 'https://messaging-service.co.tz/api/sms/v1/text/single'),
        ],

        'monex' => [
            'api_token' => env('MONEX_API_TOKEN'),
            'sender_id' => env('MONEX_SENDER_ID', 'MAGICBUILD'),
            'base_url' => env('MONEX_BASE_URL', 'https://sms.monex.co.tz/api/v3/sms'),
            'dlt_template_id' => env('MONEX_DLT_TEMPLATE_ID'), // Optional DLT template ID
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Settings
    |--------------------------------------------------------------------------
    |
    | General SMS configuration options
    |
    */

    'settings' => [

        // Maximum message length (characters)
        'max_message_length' => 1000,

        // Default country code for phone number formatting
        'default_country_code' => '255',

        // Rate limiting (messages per minute)
        'rate_limit' => 60,

        // Delivery status check interval (minutes)
        'status_check_interval' => 30,

        // Maximum retry attempts for failed messages
        'max_retry_attempts' => 3,

        // Queue name for SMS jobs
        'queue_name' => 'sms',

        // Enable/disable SMS functionality
        'enabled' => env('SMS_ENABLED', true),

        // Log all SMS activities
        'log_activities' => env('SMS_LOG_ACTIVITIES', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Features
    |--------------------------------------------------------------------------
    |
    | Feature support matrix for different providers
    |
    */

    'provider_features' => [

        'beem' => [
            'bulk_send' => false,
            'contact_list_campaigns' => false,
            'message_retrieval' => false,
            'delivery_status' => true,
            'scheduled_messages' => false,
        ],

        'nextsms' => [
            'bulk_send' => false,
            'contact_list_campaigns' => false,
            'message_retrieval' => false,
            'delivery_status' => true,
            'scheduled_messages' => false,
        ],

        'monex' => [
            'bulk_send' => true,
            'contact_list_campaigns' => true,
            'message_retrieval' => true,
            'delivery_status' => true,
            'scheduled_messages' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Default message templates that can be used in campaigns
    |
    */

    'default_templates' => [

        [
            'title' => 'Welcome Message',
            'content' => 'Welcome to Magic Builders! We are excited to have you on board. For any assistance, please contact our support team.',
        ],

        [
            'title' => 'Site Visit Reminder',
            'content' => 'This is a reminder about your scheduled site visit today. Please ensure you have all necessary equipment and documentation.',
        ],

        [
            'title' => 'Training Notification',
            'content' => 'You have been enrolled in a new training session. Please check your dashboard for more details and schedule.',
        ],

        [
            'title' => 'Payment Notification',
            'content' => 'Your payment has been processed successfully. Transaction details have been sent to your registered email address.',
        ],

        [
            'title' => 'System Maintenance',
            'content' => 'The system will undergo scheduled maintenance from {start_time} to {end_time}. Please save your work and log out before this time.',
        ],

    ],

];

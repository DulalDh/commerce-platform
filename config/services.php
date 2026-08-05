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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'api_url' => env('SSLCOMMERZ_API_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
    ],

    'bkash' => [
        'app_key' => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'api_url' => env('BKASH_API_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/checkout'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_secret' => env('PAYPAL_WEBHOOK_SECRET'),
        'api_url' => env('PAYPAL_API_URL', 'https://www.sandbox.paypal.com/checkoutnow'),
    ],

    'nagad' => [
        'merchant_id' => env('NAGAD_MERCHANT_ID'),
        'merchant_private_key' => env('NAGAD_MERCHANT_PRIVATE_KEY'),
        'api_url' => env('NAGAD_API_URL', 'https://sandbox.mynagad.com/checkout'),
    ],

    'rocket' => [
        'merchant_id' => env('ROCKET_MERCHANT_ID'),
        'merchant_secret' => env('ROCKET_MERCHANT_SECRET'),
        'api_url' => env('ROCKET_API_URL', 'https://sandbox.rocket.com.bd/checkout'),
    ],

    'bank_transfer' => [
        'account_name' => env('BANK_TRANSFER_ACCOUNT_NAME'),
        'account_number' => env('BANK_TRANSFER_ACCOUNT_NUMBER'),
        'bank_name' => env('BANK_TRANSFER_BANK_NAME'),
        'routing_number' => env('BANK_TRANSFER_ROUTING_NUMBER'),
    ],

    'pathao' => [
        'client_secret' => env('PATHAO_CLIENT_SECRET'),
    ],

    'steadfast' => [
        'api_secret' => env('STEADFAST_API_SECRET'),
    ],

    'redx' => [
        'api_secret' => env('REDX_API_SECRET'),
    ],

    'sundarban' => [
        'api_secret' => env('SUNDARBAN_API_SECRET'),
    ],

];

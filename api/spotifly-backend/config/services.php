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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mandrill' => [
        'mode' => env('MANDRILL_MODE'),
        'white_list' => env('MANDRILL_WHITELIST'),
        'api_key' => env('MANDRILL_API_KEY'),
        'sender_email' => env('MANDRILL_SENDER_EMAIL'),
        'sender_name' => env('MANDRILL_SENDER_NAME'),
        'logo_image' => env('MANDRILL_LOGO_IMAGE'),
        'brand_url' => env('MANDRILL_BRAND_URL'),
        'brand_name' => env('MANDRILL_BRAND_NAME'),
        'templates' => [
            'register_user' => env('MANDRILL_TEMPLATE_REGISTER_USER', 'spotifly-register-user'),
            'reset_password' => env('MANDRILL_TEMPLATE_RESET_PASSWORD', 'spotifly-user-reset-password'),
        ],
    ],

    'ml_api' => [
        'url' => env('ML_API_URL', 'http://localhost:8000'),
    ],

];

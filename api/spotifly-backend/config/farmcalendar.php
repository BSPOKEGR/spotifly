<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Farm Calendar API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Farm Calendar API service.
    |
    */
    'api_url' => env('OPENAGRI_FARMCALENDAR_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The API version to use for endpoints.
    |
    */
    'api_version' => env('FARMCALENDAR_API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds.
    |
    */
    'timeout' => (int) env('FARMCALENDAR_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | Authentication settings for the Farm Calendar API.
    | If JWT token is required, set it here.
    |
    */
    'auth' => [
        'type' => env('FARMCALENDAR_AUTH_TYPE', null), // 'bearer', 'basic', null
        'token' => env('FARMCALENDAR_API_TOKEN', null),
        'username' => env('FARMCALENDAR_API_USERNAME', null),
        'password' => env('FARMCALENDAR_API_PASSWORD', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Format
    |--------------------------------------------------------------------------
    |
    | Preferred response format (Json or JsonLD).
    |
    */
    'format' => env('FARMCALENDAR_API_FORMAT', 'jsonld'),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed requests.
    |
    */
    'retry' => [
        'times' => (int) env('FARMCALENDAR_RETRY_TIMES', 3),
        'sleep' => (int) env('FARMCALENDAR_RETRY_SLEEP', 1000), // milliseconds
    ],
];

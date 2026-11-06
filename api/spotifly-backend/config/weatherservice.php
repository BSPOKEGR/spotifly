<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Weather Service API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Weather Service API.
    |
    */
    'api_url' => env('WEATHERSERVICE_API_URL', 'http://openagri-weatherservice-app-1:8010'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds.
    |
    */
    'timeout' => (int) env('WEATHERSERVICE_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | Authentication settings for the Weather Service API.
    | If JWT token is required, set it here.
    |
    */
    'auth' => [
        'type' => env('WEATHERSERVICE_AUTH_TYPE', null), // 'bearer', null
        'token' => env('WEATHERSERVICE_API_TOKEN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed requests.
    |
    */
    'retry' => [
        'times' => (int) env('WEATHERSERVICE_RETRY_TIMES', 3),
        'sleep' => (int) env('WEATHERSERVICE_RETRY_SLEEP', 1000), // milliseconds
    ],
];

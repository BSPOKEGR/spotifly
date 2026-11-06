<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pest & Disease Management API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Pest & Disease Management API.
    |
    */
    'api_url' => env('OPENAGRI_PEST_DISEASE_URL', 'http://localhost:8003'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The API version to use for endpoints.
    |
    */
    'api_version' => env('PESTDISEASE_API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds.
    |
    */
    'timeout' => (int) env('PESTDISEASE_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | Authentication settings for the Pest & Disease Management API.
    |
    */
    'auth' => [
        'username' => env('PESTDISEASE_API_USERNAME', null),
        'password' => env('PESTDISEASE_API_PASSWORD', null),
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
        'times' => (int) env('PESTDISEASE_RETRY_TIMES', 3),
        'sleep' => (int) env('PESTDISEASE_RETRY_SLEEP', 1000), // milliseconds
    ],
];

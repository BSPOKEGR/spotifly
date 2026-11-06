<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Gatekeeper API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Gatekeeper API service.
    |
    */
    'api_url' => env('OPENAGRI_GATEKEEPER_URL', 'http://localhost:8001'),

    /*
    |--------------------------------------------------------------------------
    | Gatekeeper Authentication
    |--------------------------------------------------------------------------
    |
    | Credentials for authenticating with Gatekeeper to get JWT tokens.
    |
    */
    'username' => env('GATEKEEPER_USERNAME', ''),
    'password' => env('GATEKEEPER_PASSWORD', ''),
];

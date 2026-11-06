<?php

declare(strict_types=1);

namespace App\Http\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '0.0.1',
    description: 'This is the API documentation for the Spotifly project.',
    title: 'Spotifly REST API'
)]
class OpenApi
{
}

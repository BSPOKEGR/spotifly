<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class HelloController extends Controller
{
    public function hello(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Hello, World!',
            'status' => 'success'
        ], Response::HTTP_OK);
    }
}

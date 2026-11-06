<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ForceJsonResponse
{
    public function handle(Request $request, \Closure $next): JsonResponse|Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

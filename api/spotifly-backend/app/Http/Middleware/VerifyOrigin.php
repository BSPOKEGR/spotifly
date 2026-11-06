<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\UnauthorizedException;

class VerifyOrigin
{
    public function handle(Request $request, \Closure $next): JsonResponse|Response
    {
        $origin = $request->header('origin');

        if ($origin === null) {
            throw new UnauthorizedException('Origin header is missing');
        }

        // Check if the request's origin matches any of the configured stateful domains
        if ($this->isStatefulDomain($origin)) {
            return $next($request);
        }

        throw new UnauthorizedException('Origin is not allowed');
    }

    private function isStatefulDomain(string $origin): bool
    {
        $origin = \parse_url($origin, PHP_URL_HOST);
        $statefulDomains = Config::get('sanctum.stateful', []);

        if (!is_array($statefulDomains)) {
            $statefulDomains = [$statefulDomains];
        }

        // Check if the origin is in the list of stateful domains
        return \in_array($origin, $statefulDomains);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\UnauthorizedException;

class IpBlocker
{
    public function handle(Request $request, \Closure $next): mixed
    {
        if (false === Config::get('misc.enable_ip_blocker')) {
            return $next($request);
        }

        $requestIp = $request->ip();

        $ips = Config::get('misc.internal_ip_whitelist');

        if (!\is_array($ips)) {
            $ips = [$ips];
        }

        if (!\in_array($requestIp, $ips)) {
            throw new UnauthorizedException('IP is not allowed');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\InvalidVerificationSignatureException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;

class ValidateSignedRequest extends ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed|null $args
     *
     * @return mixed
     * @throws InvalidVerificationSignatureException
     */
    public function handle($request, Closure $next, ...$args): mixed
    {
        // some of signed requests have to be sent in external clients, like email verification.
        // instead of manipulating strings allow relative validation

        $absolute = true;
        if (!empty($args) && $args[0] === 'false') {
            $absolute = false;
        }

        if (! $request->hasValidSignature($absolute)) {
            throw new InvalidVerificationSignatureException();
        }

        return $next($request);
    }
}

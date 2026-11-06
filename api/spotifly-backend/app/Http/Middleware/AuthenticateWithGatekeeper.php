<?php

namespace App\Http\Middleware;

use App\Services\GatekeeperService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithGatekeeper
{
    public function __construct(
        private GatekeeperService $gatekeeperService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'No token provided'
            ], 401);
        }

        try {
            // Verify token with Gatekeeper
            $isValid = $this->gatekeeperService->verifyToken($token);

            if (!$isValid) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Invalid or expired token'
                ], 403);
            }

            // Extract user info from token and store in request
            $userData = $this->gatekeeperService->getUserFromToken($token);
            $request->attributes->set('gatekeeper_user', $userData);
            $request->attributes->set('gatekeeper_token', $token);

        } catch (\Exception $e) {
            Log::error('Gatekeeper authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Token verification failed'
            ], 403);
        }

        return $next($request);
    }
}

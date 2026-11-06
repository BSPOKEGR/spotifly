<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GatekeeperService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('gatekeeper.api_url'), '/');
        $this->timeout = 30;
    }

    /**
     * Login user via Gatekeeper
     */
    public function login(string $username, string $password): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/login/", [
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful() && $response->json('success')) {
                return [
                    'success' => true,
                    'access_token' => $response->json('access'),
                    'refresh_token' => $response->json('refresh'),
                    'user' => $this->getUserFromToken($response->json('access')),
                ];
            }

            Log::warning('Gatekeeper login failed', [
                'username' => $username,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        } catch (\Exception $e) {
            Log::error('Gatekeeper login error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Authentication service unavailable',
            ];
        }
    }

    /**
     * Register user via Gatekeeper
     */
    public function register(array $data): array
    {
        try {
            Log::info('Attempting Gatekeeper registration', [
                'username' => $data['username'] ?? null,
                'email' => $data['email'] ?? null,
            ]);

            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/register/", $data);

            Log::info('Gatekeeper registration response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                // Auto-login after registration
                if (isset($data['username']) && isset($data['password'])) {
                    Log::info('Attempting auto-login after registration');
                    $loginResult = $this->login($data['username'], $data['password']);

                    if (!$loginResult['success']) {
                        Log::warning('Auto-login failed after registration', [
                            'username' => $data['username'],
                            'login_result' => $loginResult,
                        ]);
                    }

                    return $loginResult;
                }

                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user' => $responseData,
                ];
            }

            Log::error('Gatekeeper registration failed', [
                'status' => $response->status(),
                'errors' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Registration failed',
                'errors' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Gatekeeper registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Registration service unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/token/refresh/", [
                    'refresh' => $refreshToken,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'access_token' => $response->json('access'),
                ];
            }

            return [
                'success' => false,
                'message' => 'Token refresh failed',
            ];
        } catch (\Exception $e) {
            Log::error('Gatekeeper token refresh error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Token refresh service unavailable',
            ];
        }
    }

    /**
     * Logout user via Gatekeeper
     */
    public function logout(string $refreshToken): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/logout/", [
                    'refresh' => $refreshToken,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Gatekeeper logout error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user info from JWT token
     */
    public function getUserFromToken(string $token): array
    {
        try {
            // Decode JWT payload (middle part)
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return [];
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return [
                'id' => $payload['user_id'] ?? null,
                'username' => $payload['username'] ?? null,
                'email' => $payload['email'] ?? null,
                'first_name' => $payload['first_name'] ?? null,
                'last_name' => $payload['last_name'] ?? null,
                'uuid' => $payload['uuid'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to decode JWT token', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Verify token is valid
     */
    public function verifyToken(string $token): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/validate_token/", [
                    'token' => $token,
                ]);

            if ($response->successful()) {
                return $response->json('success') === true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user profile using token
     */
    public function getProfile(string $token): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get("{$this->baseUrl}/api/profile/");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get user profile', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

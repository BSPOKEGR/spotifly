<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GatekeeperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private GatekeeperService $gatekeeper
    ) {}

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register User via Gatekeeper',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'email', 'password', 'password2'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password2', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'username', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        // Note: Gatekeeper does not currently support user registration via API
        // Users must be created through Gatekeeper's Django admin interface
        return response()->json([
            'success' => false,
            'message' => 'User registration is not available through the API. Please contact your administrator to create an account.',
            'note' => 'Users must be created through Gatekeeper admin interface'
        ], 501); // 501 Not Implemented

        // Original registration code (commented out since Gatekeeper doesn't support it)
        /*
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:150',
            'email' => 'required|email|max:254',
            'password' => 'required|string|min:8',
            'password2' => 'required|same:password',
            'first_name' => 'nullable|string|max:150',
            'last_name' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gatekeeper->register($request->all());

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 400);
        */
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login User via Gatekeeper',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'spadmin'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'your-password'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'username', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gatekeeper->login(
            $request->input('username'),
            $request->input('password')
        );

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 401);
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh Access Token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'access_token', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->gatekeeper->refreshToken($request->input('refresh_token'));
        return response()->json($result, $result['success'] ? 200 : 401);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout User',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');
        if ($refreshToken) {
            $this->gatekeeper->logout($refreshToken);
        }
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    #[OA\Get(
        path: '/api/auth/user',
        summary: 'Get authenticated user profile',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'User profile'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No token provided'], 401);
        }

        $profile = $this->gatekeeper->getProfile($token) ?? $this->gatekeeper->getUserFromToken($token);
        if (empty($profile)) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
        }

        return response()->json($profile);
    }
}

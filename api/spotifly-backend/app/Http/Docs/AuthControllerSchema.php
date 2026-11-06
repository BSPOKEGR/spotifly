<?php

declare(strict_types=1);

namespace App\Http\Docs;

use OpenApi\Attributes as OA;

class AuthControllerSchema
{
    #[OA\Schema(
        schema: 'UserRegistrationRequest',
        description: 'Request body for user registration.',
        required: [
            'name',
            'email',
            'password',
        ],
        properties: [
            new OA\Property(
                property: 'name',
                description: 'Name for the user.',
                type: 'string',
                maxLength: 50,
                minLength: 3,
                example: 'John Doe'
            ),
            new OA\Property(
                property: 'password',
                description: 'Password for the user, minimum length of 8 characters.',
                type: 'string',
                minLength: 8,
                example: 'password123'
            ),
            new OA\Property(
                property: 'email',
                description: 'Email address of the user, must be a valid email format.',
                type: 'string',
                format: 'email',
                maxLength: 100,
                example: 'john.doe@mail.com'
            ),
        ],
        type: 'object'
    )]
    public function register(): void
    {
    }

    #[OA\Schema(
        schema: 'UserLoginRequest',
        description: 'Request body for user login.',
        required: [
            'email',
            'password',
        ],
        properties: [
            new OA\Property(
                property: 'email',
                description: 'Email address of the user',
                type: 'string',
                format: 'email',
                maxLength: 100,
                example: 'john.doe@mail.com'
            ),
            new OA\Property(
                property: 'password',
                description: 'Password for the user',
                type: 'string',
                minLength: 8,
                example: 'password123'
            ),
        ],
        type: 'object'
    )]
    public function login(): void
    {
    }

    #[OA\Schema(
        schema: 'UserProfileResponse',
        description: 'Response body for user profile.',
        required: [
            'id',
            'name',
            'email',
            'emailVerifiedAt',
        ],
        properties: [
            new OA\Property(
                property: 'id',
                description: 'ID of the user.',
                type: 'integer',
                format: 'int64',
                example: 1
            ),
            new OA\Property(
                property: 'name',
                description: 'Name of the user.',
                type: 'string',
                maxLength: 50,
                example: 'John Doe'
            ),
            new OA\Property(
                property: 'email',
                description: 'Email address of the user.',
                type: 'string',
                format: 'email',
                maxLength: 100,
                example: 'john.doe@mail.com'
            ),
            new OA\Property(
                property: 'emailVerifiedAt',
                description: 'Date and time when the email was verified.',
                type: 'string',
                format: 'date-time',
                example: '2024-11-19 19:33:53'
            ),
        ],
        type: 'object'
    )]
    public function profile(): void
    {
    }
}

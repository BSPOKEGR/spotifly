<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Register fail
     */
    public function testFailRegisterValidation()
    {
        $payload = [];

        $errors = [
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ];

        $this->json('POST', route('auth.register'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    /**
     * Register success
     */
    public function testSuccessRegister()
    {
        $payload = [
            'name' => 'lala_name',
            'email' => 'lala1@lala.qq',
            'password' => '1234567890',
        ];

        $this->json('POST', route('auth.register'), $payload)
            ->assertStatus(204);

        $this->assertDatabaseHas('users', ['email' => 'lala1@lala.qq']);

        $user = User::where('email', 'lala1@lala.qq')->first();

        $this->assertNotNull($user->settings);
    }
}

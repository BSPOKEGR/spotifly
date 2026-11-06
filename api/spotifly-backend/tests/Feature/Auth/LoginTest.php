<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Login Validation fail
     */
    public function testFailLoginValidation()
    {
        $payload = [];

        $errors = [
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ];

        $this->json('POST', route('auth.login'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    /**
     * Login Inactive User Fail
     */
    public function testFailLoginInactiveUser()
    {
        $payload = [
            'name' => 'lala_name',
            'email' => 'lala1@lala.qq',
            'password' => '1234567890',
        ];

        $this->json('POST', route('auth.register'), $payload)
            ->assertStatus(204);

        $this->json('POST', route('auth.login'), $payload)
            ->assertStatus(401);
    }

    /**
     * Login Success
     */
    public function testSuccessLogin()
    {
        $payload = [
            'name' => 'lala_name',
            'email' => 'lala1@lala.qq',
            'password' => '1234567890',
        ];

        $this->json('POST', route('auth.register'), $payload)
            ->assertStatus(204);

        // in order for a user to login we must set email verified at and active
        $user = User::where('email', 'lala1@lala.qq')->first();

        $this->assertEquals(null, $user->email_verified_at);
        $this->assertEquals(false, $user->active);

        $user->email_verified_at = now();
        $user->save();

        $this->json('POST', route('auth.login'), $payload)
            ->assertStatus(401);

        $user->active = true;
        $user->save();

        $this->json('POST', route('auth.login'), $payload)
            ->assertStatus(204);
    }
}

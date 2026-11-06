<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => null,
        ]);
    }

    public function testVerifyEmailSuccessfully(): void
    {
        // Generate a signed URL for the email verification route
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ],
            absolute: false
        );

        $this->get($verificationUrl)->assertStatus(204);

        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
        $this->assertEquals(true, $this->user->active);
    }

    public function testVerifyEmailFailsWithInvalidSignature(): void
    {
        // Generate an invalid URL (tamper with the signature)
        $invalidUrl = URL::route('verification.verify', [
                'id' => $this->user->id,
                'hash' => sha1($this->user->getEmailForVerification()),
            ]) . '?signature=invalid-signature';

        $response = $this->get($invalidUrl);

        $errors = [
            'code' => 401,
            'description' => 'Unauthorized',
            'internal_code' => 140103,
            'internal_description' => 'invalid-signature'
        ];

        $response->assertStatus(401);
        $response->assertJson($errors);

        $this->assertNull($this->user->fresh()->email_verified_at);
    }
}

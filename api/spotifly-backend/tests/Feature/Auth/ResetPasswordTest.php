<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'active' => true,
        ]);
    }

    public function testResetPasswordSuccess()
    {
        $key = Config::get('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->first();

        $this->assertNull($resetToken);

        $forgotPayload = [
            'email' => $this->user->email,
        ];

        $this->json('GET', route('password.request'), $forgotPayload)
            ->assertStatus(204);

        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->first();

        $this->assertNotNull($resetToken);

        $newPassword = Str::random(10);
        // create a dummy token just to check reset password functionality
        $dummyToken = hash_hmac('sha256', Str::random(40), $key);

        DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->update(['token' => Hash::make($dummyToken)]);

        $resetPayload = [
            'email' => $this->user->email,
            'token' => $dummyToken,
            'password' => $newPassword,
        ];

        $this->json('GET', route('user.reset-password'), $resetPayload)
            ->assertStatus(204);
    }

    public function testResetPasswordFail()
    {
        $payload = [
            'email' => 'lala1@lala.qq',
        ];

        $this->json('GET', route('password.request'), $payload)
            ->assertStatus(404);

        $resetPayload = [
            'email' => $this->user->email,
            'token' => 'lala',
            'password' => '21312312312312312312',
        ];

        $this->json('GET', route('user.reset-password'), $resetPayload)
            ->assertStatus(404);
    }
}

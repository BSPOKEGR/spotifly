<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        // Create a user to authenticate
        $this->user = User::factory()->create();
    }

    public function testLogoutSuccess()
    {
        // Authenticate the user
        $this->actingAs($this->user);

        $this->assertTrue(Auth::check());

        $this->postJson(route('auth.logout'))
            ->assertStatus(204);

        // Assert the user is no longer authenticated
        $this->assertFalse(Auth::check());

        // Assert the session is invalidated
        $this->assertGuest();
    }
}

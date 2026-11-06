<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private UserRepository $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->userRepository =  $this->app->make(UserRepository::class);
    }

    public function testRegister(): void
    {
        $name = 'lalakis';
        $email = 'lalakis@gmail.com';
        $password = 'lalakisPass';

        $user = $this->userRepository->register($name, $email, $password);

        $this->assertNotNull($user);
        $this->assertEquals($name, $user->name);
        $this->assertEquals($email, $user->email);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function testResetPassword(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'active' => false,
        ]);
        $user->createToken('Test Token')->plainTextToken;

        $newPassword = 'newPassword';

        $this->userRepository->resetPassword($user, $newPassword);

        $user->refresh();

        $this->assertTrue(Hash::check($newPassword, $user->password));

        $this->assertEmpty($user->tokens);
    }
}

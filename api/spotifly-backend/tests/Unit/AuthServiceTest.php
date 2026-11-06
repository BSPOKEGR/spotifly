<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dto\Users\RegisterData;
use App\Dto\Users\ResetPasswordData;
use App\Events\UserRegisterEvent;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\UserSettingsRepository;
use App\Services\AuthService;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private AuthService $authService;
    private PasswordBroker $passwordBroker;
    private UserSettingsRepository $userSettingsRepository;
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->passwordBroker = Password::broker();
        $this->userSettingsRepository = new UserSettingsRepository();
        $this->authService = new AuthService(new UserRepository(), $this->userSettingsRepository, $this->passwordBroker);

        $this->user = User::factory()->create([
            'email_verified_at' => null,
            'active' => false,
        ]);
    }

    public function testRegister(): void
    {
        $payload = [
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => '1234567890',
        ];

        $payload = RegisterData::from($payload);

        $mockUserRepository = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([])
            ->onlyMethods(['register'])->getMock();

        $mockUserRepository->expects($this->exactly(1))
            ->method('register')
            ->with($payload->name, $payload->email, $payload->password)
            ->willReturn($this->user);

        $mockUserSettingsRepository = $this->getMockBuilder(UserSettingsRepository::class)
            ->setConstructorArgs([])
            ->onlyMethods(['createUserSettings'])->getMock();

        $mockUserSettingsRepository->expects($this->exactly(1))
            ->method('createUserSettings')
            ->with($this->user);

        $authService = new AuthService($mockUserRepository, $mockUserSettingsRepository, $this->passwordBroker);
        $authService->register($payload);

        Event::assertDispatched(UserRegisterEvent::class);
    }

    public function testVerifyEmailSuccess(): void
    {
        $randomDate = Carbon::create(2024, 5, 21, 12);
        Carbon::setTestNow($randomDate);

        $mockBuilder = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([])
            ->onlyMethods(['find', 'updateModel'])->getMock();

        $mockBuilder->expects($this->exactly(1))
            ->method('find')
            ->with($this->user->id)
            ->willReturn($this->user);

        $payloadForUpdateModel = [
            'email_verified_at' => now(),
            'active' => true
        ];
        $mockBuilder->expects($this->exactly(1))
            ->method('updateModel')
            ->with($this->user, $payloadForUpdateModel);

        $authService = new AuthService($mockBuilder, $this->userSettingsRepository, $this->passwordBroker);

        $authService->verifyEmail($this->user->id, sha1($this->user->email));
    }

    public function testVerifyEmailNoAction(): void
    {
        $this->user->email_verified_at = now()->format('Y-m-d H:i:s');
        $this->user->save();
        $this->user->refresh();

        $mockBuilder = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([])
            ->onlyMethods(['find', 'updateModel'])->getMock();

        $mockBuilder->expects($this->exactly(1))
            ->method('find')
            ->with($this->user->id)
            ->willReturn($this->user);

        $mockBuilder->expects($this->exactly(0))
            ->method('updateModel');

        $authService = new AuthService($mockBuilder, $this->userSettingsRepository, $this->passwordBroker);

        $authService->verifyEmail($this->user->id, sha1($this->user->email));
    }

    public function testVerifyEmailWrongHash(): void
    {

        $mockBuilder = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([])
            ->onlyMethods(['find', 'updateModel'])->getMock();

        $mockBuilder->expects($this->exactly(1))
            ->method('find')
            ->with($this->user->id)
            ->willReturn($this->user);

        $mockBuilder->expects($this->exactly(0))
            ->method('updateModel');

        $authService = new AuthService($mockBuilder, $this->userSettingsRepository, $this->passwordBroker);

        $this->expectException(\Exception::class);
        $authService->verifyEmail($this->user->id, 'test1234');
    }

    public function testIsEmailVerified(): void
    {
        $response = $this->authService->isEmailVerified($this->user);

        $this->assertFalse($response);

        $this->user->email_verified_at = Carbon::now()->format('Y-m-d H:i:s');
        $this->user->save();
        $this->user->refresh();

        $response = $this->authService->isEmailVerified($this->user);
        $this->assertTrue($response);
    }

    public function testIsUserActive(): void
    {
        $response = $this->authService->isUserActive($this->user);

        $this->assertFalse($response);

        $this->user->active = true;
        $this->user->save();
        $this->user->refresh();

        $response = $this->authService->isUserActive($this->user);

        $this->assertTrue($response);
    }

    public function testResetUserPasswordSuccess(): void
    {

        $key = Config::get('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $newPassword = Str::random(10);
        // create a dummy token just to check reset password functionality
        $dummyToken = hash_hmac('sha256', Str::random(40), $key);

        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make($dummyToken),
            'created_at' => now(),
        ]);

        $resetPayload = [
            'email' => $this->user->email,
            'token' => $dummyToken,
            'password' => $newPassword,
        ];

        $payload = ResetPasswordData::from($resetPayload);

        $response = $this->authService->resetUserPassword($payload);

        $this->assertEquals(Password::PASSWORD_RESET, $response);
    }

    public function testResetUserPasswordFail(): void
    {
        $resetPayload = [
            'email' => $this->user->email,
            'token' => 'test',
            'password' => 'randomPassword',
        ];

        $payload = ResetPasswordData::from($resetPayload);

        $response = $this->authService->resetUserPassword($payload);

        $this->assertEquals(Password::INVALID_TOKEN, $response);

        $key = Config::get('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $newPassword = Str::random(10);
        // create a dummy token just to check reset password functionality
        $dummyToken = hash_hmac('sha256', Str::random(40), $key);

        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make($dummyToken),
            'created_at' => now(),
        ]);

        $resetPayload = [
            'email' => 'randomEmailNotExist@mail.com',
            'token' => $dummyToken,
            'password' => $newPassword,
        ];

        $payload = ResetPasswordData::from($resetPayload);

        $response = $this->authService->resetUserPassword($payload);

        $this->assertEquals(Password::INVALID_USER, $response);
    }

    public function testCheckHashPassword(): void
    {
        $oldPassword = 'password';
        $hashedPassword = Hash::make($oldPassword);

        $response = $this->authService->checkHashPassword('password', $hashedPassword);
        $this->assertTrue($response);

        $response = $this->authService->checkHashPassword('passwordFalse', $hashedPassword);
        $this->assertFalse($response);
    }
}

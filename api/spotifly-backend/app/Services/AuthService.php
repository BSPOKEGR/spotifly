<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\Users\RegisterData;
use App\Dto\Users\ResetPasswordData;
use App\Events\UserRegisterEvent;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\UserSettingsRepository;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserSettingsRepository $userSettingsRepository,
        private readonly PasswordBroker $passwordBroker
    ) {
    }

    public function register(RegisterData $data): void
    {
        DB::beginTransaction();

        try {
            $user = $this->userRepository->register($data->name, $data->email, $data->password);

            $this->userSettingsRepository->createUserSettings($user);

            event(new UserRegisterEvent($user, $data->password));
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();
    }

    public function verifyEmail(int $id, string $hash): void
    {
        $user = $this->userRepository->find($id);

        if ($user->email_verified_at !== null) {
            return;
        }

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new \Exception('Invalid verification link');
        }

        $this->userRepository->updateModel($user, ['email_verified_at' => now(), 'active' => true]);
    }

    public function isEmailVerified(User $user): bool
    {
        return null !== $user->email_verified_at;
    }

    public function isUserActive(User $user): bool
    {
        return $user->active;
    }

    public function resetUserPassword(ResetPasswordData $data): mixed
    {
        return $this->passwordBroker->reset($data->toArray(), function ($user, $password) {
            $this->userRepository->resetPassword($user, $password);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function register(string $name, string $email, string $password): User
    {
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;

        $user->save();

        return $user;
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->password = $password;
        $user->tokens()->delete();
        $user->save();
    }
}

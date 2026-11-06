<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UserRegisterEvent
{
    use SerializesModels;

    /**
     * User.
     *
     * @var User
     */
    private $user;

    /**
     * Random generated password (only for farmers created by consultants).
     *
     * @var string
     */
    private ?string $password = null;

    /**
     * Create a new event instance.
     *
     * @param User        $user
     * @param string|null $password
     *
     * @return void
     */
    public function __construct(User $user, ?string $password = null )
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get password
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
}

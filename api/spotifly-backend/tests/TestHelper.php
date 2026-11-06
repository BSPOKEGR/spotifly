<?php

declare(strict_types=1);

namespace Tests;

use Laravel\Sanctum\Sanctum;

class TestHelper
{
    public function login(User $user): void
    {
        Sanctum::actingAs($user, ['*']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class EnsureRequestsAreStateful extends EnsureFrontendRequestsAreStateful
{
    /**
     * This was created to override the default configuration of the secure cookie sessions that set same_site = lax.
     */
    protected function configureSecureCookieSessions(): void
    {
        return;
    }
}

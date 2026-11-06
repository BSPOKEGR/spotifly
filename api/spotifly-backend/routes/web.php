<?php

declare(strict_types=1);

use App\Http\Middleware\IpBlocker;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

// need to manually add the CSRF cookie route to exclude ipblocker middleware
Route::get('sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->withoutMiddleware([IpBlocker::class])
    ->name('sanctum.csrf-cookie');

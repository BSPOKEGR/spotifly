<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LocaleService;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

readonly class Localization
{
    /**
     * Localization constructor.
     */
    public function __construct(private Application $app, private LocaleService $localeService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // check user locale first if request is authenticated
        // if user locale is null, check headers
        $locale = $request->user()?->preferredLocale()
            ?? $this->getContentLanguageLocale()
            ?? $this->getAcceptedLanguageLocale();

        // if locale is not empty set the locale
        if (null !== $locale) {
            $this->app->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Get Accept-Language header
     */
    private function getAcceptedLanguageLocale(): string|null
    {
        $headerValues = request()->getLanguages();

        return $this->localeService->getLocaleInOrder($headerValues);
    }

    /**
     * Get Content-Language header
     */
    private function getContentLanguageLocale(): string|null
    {
        $locale = request()->header('Content-Language');
        if (empty($locale)) {
            return null;
        }

        return $this->localeService->getLocaleInOrder([$locale]);
    }
}

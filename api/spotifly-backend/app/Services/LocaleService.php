<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LanguageRepository;

readonly class LocaleService
{
    public function __construct(
        private LanguageRepository $languageRepository
    ) {
    }

    /**
     * Get locale in order
     *
     * @param array<string> $locales
     */
    public function getLocaleInOrder(array $locales): ?string
    {
        if (empty($locales)) {
            return null;
        }

        $getLanguages = $this->languageRepository->pluckByKey('locale', $locales);

        foreach ($locales as $locale) {
            if ($getLanguages->contains($locale)) {
                return $locale;
            }
        }

        return null;
    }
}

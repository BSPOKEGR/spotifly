<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Language;
use App\Repositories\LanguageRepository;
use App\Services\LocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LocaleService $languageService;

    public function setUp(): void
    {
        parent::setUp();

        $this->languageService = new LocaleService(new LanguageRepository());

        $this->setLanguages();
    }

    public function testGetLocaleInOrderReturnsEmptyArrayWhenLocalesAreEmpty(): void
    {
        $result = $this->languageService->getLocaleInOrder([]);

        $this->assertNull($result);
    }

    public function testGetLocaleInOrderReturnsLanguagesInCorrectOrder(): void
    {
        $locales = ['fr', 'en', 'es'];
        $langFr = Language::query()->where('locale', 'fr')->first();

        $result = $this->languageService->getLocaleInOrder($locales);

        $this->assertEquals($langFr->locale, $result);
    }

    public function testGetLocaleInOrderReturnsOnlyExistingLanguages(): void
    {
        $langFr = Language::query()->where('locale', 'fr')->first();

        $locales = ['fr', 'it', 'de'];

        $result = $this->languageService->getLocaleInOrder($locales);

        $this->assertEquals($langFr->locale, $result);
    }

    public function testGetLocaleInOrderReturnsLanguagesInGivenOrder(): void
    {
        $langEs = Language::query()->where('locale', 'es')->first();

        $locales = ['es', 'fr', 'en'];

        $result = $this->languageService->getLocaleInOrder($locales);

        $this->assertEquals($langEs->locale, $result);
    }

    private function setLanguages(): void
    {
        $languages = [
            [
                'id' => 3,
                'name' => 'french',
                'locale' => 'fr',
            ],
            [
                'id' => 4,
                'name' => 'spanish',
                'locale' => 'es',
            ],
        ];

        Language::query()->insert($languages);
    }
}

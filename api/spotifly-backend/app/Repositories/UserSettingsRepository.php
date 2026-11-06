<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Language;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\App;

/**
 * @extends BaseRepository<UserSetting>
 */
class UserSettingsRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserSetting::class);
    }

    public function createUserSettings(User $user, ?string $locale = null): void
    {
        $userSettings = new UserSetting();
        // get language
        // first check given locale, if no locale were supplied
        // or the given locale was invalid then use default locale.
        $language = Language::where('locale', $locale)->first();
        if (null === $language) {
            $language = Language::where('locale', App::getLocale())->first();
        }

        $userSettings->language()->associate($language);

        $user->settings()->save($userSettings);
    }
}

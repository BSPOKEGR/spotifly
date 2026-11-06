<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Language;

/**
 * @extends BaseRepository<Language>
 */
class LanguageRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Language::class);
    }
}

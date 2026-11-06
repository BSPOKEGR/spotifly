<?php

declare(strict_types=1);

namespace App\Dto\Users;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ForgotPasswordData extends Data
{
    public function __construct(
        public string $email,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'email' => ValidationRules::Email->addRequired(),
        ];
    }
}

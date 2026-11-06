<?php

declare(strict_types=1);

namespace App\Dto\Users;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ResetPasswordData extends Data
{
    public function __construct(
        public string $email,
        public string $password,
        public string $token,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'password' => ValidationRules::Password->addRequired(),
            'email' => ValidationRules::Email->addRequired(),
            'token' => ValidationRules::Token->addRequired(),
        ];
    }
}

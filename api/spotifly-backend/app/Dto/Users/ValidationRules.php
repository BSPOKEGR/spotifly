<?php

declare(strict_types=1);

namespace App\Dto\Users;

use App\Dto\ValidationRulesTrait;

enum ValidationRules: string
{
    use ValidationRulesTrait;

    case Name = 'name';
    case Email = 'email';
    case Password = 'password';
    case Token = 'token';

    /**
     * @return array<string>
     */
    public function rules(): array
    {
        return match ($this) {
            self::Name => ['string', 'min:3', 'max:50'],
            self::Email => ['email', 'max:100'],
            self::Password => ['string', 'min:8'],
            self::Token => ['string'],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Users;

use App\Transformers\LowercaseTransformer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class RegisterData extends Data
{
    public function __construct(
        public string $name,
        #[WithCast(LowercaseTransformer::class)]
        public string $email,
        public string $password
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ValidationRules::Name->addRequired(),
            'email' => ValidationRules::Email->updateRules(['required', 'unique:users,email'], []),
            'password' => ValidationRules::Password->addRequired(),
        ];
    }
}

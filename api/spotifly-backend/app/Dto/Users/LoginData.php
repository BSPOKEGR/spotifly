<?php

declare(strict_types=1);

namespace App\Dto\Users;

use App\Transformers\LowercaseTransformer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class LoginData extends Data
{
    public function __construct(
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
            'email' => ValidationRules::Email->addRequired(),
            'password' => ValidationRules::Password->updateRules(['required'], ['min:8']),
        ];
    }
}

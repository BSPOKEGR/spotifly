<?php

declare(strict_types=1);

namespace App\Dto\UsersRegister;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class VerificationUrlData extends Data
{
    public function __construct(
        public int $userId,
        #[Email]
        public string $email,
    ) {
    }
}

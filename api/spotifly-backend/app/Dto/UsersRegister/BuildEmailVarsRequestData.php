<?php

declare(strict_types=1);

namespace App\Dto\UsersRegister;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class BuildEmailVarsRequestData extends Data
{
    public function __construct(
        public User $user,
        public string $webUrl,
        public ?string $password,
    ) {
    }
}

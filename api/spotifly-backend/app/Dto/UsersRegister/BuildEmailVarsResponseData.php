<?php

declare(strict_types=1);

namespace App\Dto\UsersRegister;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class BuildEmailVarsResponseData extends Data
{
    public function __construct(
        public string $subject,
        public string $title,
        public string $salutation,
        public string $welcome1,
        public ?string $loginInfo,
        public ?string $goodbye,
        public ?string $loginUrl,
        public ?string $confirmButton,
        public ?string $confirmInfo,
        public ?string $confirmUrl,
        public string $logoImage,
        public string $brandUrl,
    ) {
    }
}

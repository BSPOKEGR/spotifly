<?php

declare(strict_types=1);

namespace App\Dto\Filters;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class FilterData extends Data
{
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value
    ) {
    }
}

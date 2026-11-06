<?php

declare(strict_types=1);

namespace App\Transformers;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Creation\CreationContext;

class LowercaseTransformer implements Cast
{
    /**
     * @param array<string> $properties
     * @param CreationContext<Data> $context
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): string
    {
        return \mb_convert_case($value, MB_CASE_LOWER, 'UTF-8');
    }
}

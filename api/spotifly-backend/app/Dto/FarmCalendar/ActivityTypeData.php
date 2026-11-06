<?php

declare(strict_types=1);

namespace App\Dto\FarmCalendar;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ActivityTypeData extends Data
{
    public function __construct(
        public string $type,
        public string $id
    ) {
    }

    public static function fromOcsm(array $data): self
    {
        return new self(
            type: $data['@type'] ?? '',
            id: $data['@id'] ?? ''
        );
    }

    public function toOcsm(): array
    {
        return [
            '@type' => $this->type,
            '@id' => $this->id,
        ];
    }
}

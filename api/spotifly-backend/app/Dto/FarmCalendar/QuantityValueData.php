<?php

declare(strict_types=1);

namespace App\Dto\FarmCalendar;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class QuantityValueData extends Data
{
    public function __construct(
        public ?string $id,
        public string $type,
        public string $unit,
        public float $numericValue
    ) {
    }

    public static function fromOcsm(array $data): self
    {
        return new self(
            id: $data['@id'] ?? null,
            type: $data['@type'] ?? 'QuantityValue',
            unit: $data['unit'] ?? '',
            numericValue: $data['numericValue'] ?? 0.0
        );
    }

    public function toOcsm(): array
    {
        $result = [
            '@type' => $this->type,
            'unit' => $this->unit,
            'numericValue' => $this->numericValue,
        ];

        if ($this->id) {
            $result['@id'] = $this->id;
        }

        return $result;
    }
}

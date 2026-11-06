<?php

declare(strict_types=1);

namespace App\Dto\FarmCalendar;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CompostMaterialData extends Data
{
    public function __construct(
        public ?string $id,
        public string $type,
        public string $typeName,
        public QuantityValueData $quantityValue
    ) {
    }

    public static function fromOcsm(array $data): self
    {
        return new self(
            id: $data['@id'] ?? null,
            type: $data['@type'] ?? 'CompostMaterial',
            typeName: $data['typeName'] ?? '',
            quantityValue: QuantityValueData::fromOcsm($data['quantityValue'] ?? [])
        );
    }

    public function toOcsm(): array
    {
        $result = [
            '@type' => $this->type,
            'typeName' => $this->typeName,
            'quantityValue' => $this->quantityValue->toOcsm(),
        ];

        if ($this->id) {
            $result['@id'] = $this->id;
        }

        return $result;
    }
}

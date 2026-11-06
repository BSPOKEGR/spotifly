<?php

declare(strict_types=1);

namespace App\Dto\FarmCalendar;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class AddRawMaterialOperationData extends Data
{
    public function __construct(
        public ?string $id,
        public string $type,
        public ActivityTypeData $activityType,
        public string $title,
        public ?string $details,
        public string $hasStartDatetime,
        public ?string $hasEndDatetime,
        public ?array $responsibleAgent,
        public array $usesAgriculturalMachinery,
        /** @var CompostMaterialData[] */
        public array $hasCompostMaterial
    ) {
    }

    public static function fromOcsm(array $data): self
    {
        $compostMaterials = [];
        if (isset($data['hasCompostMaterial'])) {
            foreach ($data['hasCompostMaterial'] as $material) {
                $compostMaterials[] = CompostMaterialData::fromOcsm($material);
            }
        }

        return new self(
            id: $data['@id'] ?? null,
            type: $data['@type'] ?? 'AddRawMaterialOperation',
            activityType: ActivityTypeData::fromOcsm($data['activityType'] ?? []),
            title: $data['title'] ?? '',
            details: $data['details'] ?? null,
            hasStartDatetime: $data['hasStartDatetime'] ?? '',
            hasEndDatetime: $data['hasEndDatetime'] ?? null,
            responsibleAgent: $data['responsibleAgent'] ?? null,
            usesAgriculturalMachinery: $data['usesAgriculturalMachinery'] ?? [],
            hasCompostMaterial: $compostMaterials
        );
    }

    public function toOcsm(): array
    {
        $result = [
            '@type' => $this->type,
            'activityType' => $this->activityType->toOcsm(),
            'title' => $this->title,
            'details' => $this->details,
            'hasStartDatetime' => $this->hasStartDatetime,
            'hasEndDatetime' => $this->hasEndDatetime,
            'responsibleAgent' => $this->responsibleAgent,
            'usesAgriculturalMachinery' => $this->usesAgriculturalMachinery,
            'hasCompostMaterial' => array_map(
                fn(CompostMaterialData $material) => $material->toOcsm(),
                $this->hasCompostMaterial
            ),
        ];

        if ($this->id) {
            $result['@id'] = $this->id;
        }

        return $result;
    }
}

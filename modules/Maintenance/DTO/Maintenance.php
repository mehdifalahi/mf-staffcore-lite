<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Maintenance\DTO;

final class Maintenance
{
    public const TYPE_REPAIR = 'REPAIR';
    public const TYPE_SERVICE = 'SERVICE';
    public const TYPE_INSPECTION = 'INSPECTION';

    public function __construct(
        public readonly int $id,
        public readonly int $assetId,
        public readonly string $maintenanceDate,
        public readonly string $type,
        public readonly ?float $cost,
        public readonly ?string $performedBy,
        public readonly ?int $performedByEmployeeId,
        public readonly ?string $nextServiceDate,
        public readonly ?string $notes,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $assetName = null,
        public readonly ?string $assetCode = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_REPAIR,
            self::TYPE_SERVICE,
            self::TYPE_INSPECTION,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            assetId: (int) ($row['asset_id'] ?? 0),
            maintenanceDate: (string) ($row['maintenance_date'] ?? ''),
            type: (string) ($row['type'] ?? self::TYPE_SERVICE),
            cost: isset($row['cost']) && $row['cost'] !== null && $row['cost'] !== ''
                ? (float) $row['cost']
                : null,
            performedBy: self::nullableString($row['performed_by'] ?? null),
            performedByEmployeeId: isset($row['performed_by_employee_id']) && $row['performed_by_employee_id'] !== ''
                && $row['performed_by_employee_id'] !== null
                ? (int) $row['performed_by_employee_id']
                : null,
            nextServiceDate: self::nullableString($row['next_service_date'] ?? null),
            notes: self::nullableString($row['notes'] ?? null),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            assetName: self::nullableString($row['asset_name'] ?? null),
            assetCode: self::nullableString($row['asset_code'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->assetId,
            'maintenance_date' => $this->maintenanceDate,
            'type' => $this->type,
            'cost' => $this->cost,
            'performed_by' => $this->performedBy,
            'performed_by_employee_id' => $this->performedByEmployeeId,
            'next_service_date' => $this->nextServiceDate,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'asset_name' => $this->assetName,
            'asset_code' => $this->assetCode,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

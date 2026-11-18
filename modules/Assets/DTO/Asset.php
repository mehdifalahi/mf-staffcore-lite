<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Assets\DTO;

final class Asset
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_UNDER_REPAIR = 'UNDER_REPAIR';
    public const STATUS_DISPOSED = 'DISPOSED';

    public const CONDITION_EXCELLENT = 'EXCELLENT';
    public const CONDITION_GOOD = 'GOOD';
    public const CONDITION_FAIR = 'FAIR';
    public const CONDITION_POOR = 'POOR';

    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly int $categoryId,
        public readonly ?string $purchaseDate,
        public readonly ?string $purchaseCost,
        public readonly ?string $currentValue,
        public readonly ?string $serialNumber,
        public readonly ?string $warrantyExpiry,
        public readonly string $status,
        public readonly string $assetCondition,
        public readonly ?int $assignedTo,
        public readonly ?string $location,
        public readonly ?int $supplierId,
        public readonly ?string $image,
        public readonly ?string $notes,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $categoryName = null,
        public readonly ?string $assignedToName = null,
        public readonly ?string $supplierName = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            code: (string) ($row['code'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            categoryId: (int) ($row['category_id'] ?? 0),
            purchaseDate: self::nullableString($row['purchase_date'] ?? null),
            purchaseCost: self::nullableDecimal($row['purchase_cost'] ?? null),
            currentValue: self::nullableDecimal($row['current_value'] ?? null),
            serialNumber: self::nullableString($row['serial_number'] ?? null),
            warrantyExpiry: self::nullableString($row['warranty_expiry'] ?? null),
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            assetCondition: (string) ($row['condition'] ?? self::CONDITION_GOOD),
            assignedTo: isset($row['assigned_to']) && $row['assigned_to'] !== '' && $row['assigned_to'] !== null
                ? (int) $row['assigned_to']
                : null,
            location: self::nullableString($row['location'] ?? null),
            supplierId: isset($row['supplier_id']) && $row['supplier_id'] !== '' && $row['supplier_id'] !== null
                ? (int) $row['supplier_id']
                : null,
            image: self::nullableString($row['image'] ?? null),
            notes: self::nullableString($row['notes'] ?? null),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            categoryName: self::nullableString($row['category_name'] ?? null),
            assignedToName: self::nullableString($row['assigned_to_name'] ?? null),
            supplierName: self::nullableString($row['supplier_name'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category_id' => $this->categoryId,
            'purchase_date' => $this->purchaseDate,
            'purchase_cost' => $this->purchaseCost,
            'current_value' => $this->currentValue,
            'serial_number' => $this->serialNumber,
            'warranty_expiry' => $this->warrantyExpiry,
            'status' => $this->status,
            'condition' => $this->assetCondition,
            'assigned_to' => $this->assignedTo,
            'location' => $this->location,
            'supplier_id' => $this->supplierId,
            'image' => $this->image,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'category_name' => $this->categoryName,
            'assigned_to_name' => $this->assignedToName,
            'supplier_name' => $this->supplierName,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

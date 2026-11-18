<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Assets\DTO;

final class AssetInput
{
    public function __construct(
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
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
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
        ];
    }
}

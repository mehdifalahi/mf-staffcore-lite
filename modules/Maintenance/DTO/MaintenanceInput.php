<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Maintenance\DTO;

final class MaintenanceInput
{
    public function __construct(
        public readonly int $assetId,
        public readonly string $maintenanceDate,
        public readonly string $type,
        public readonly ?float $cost,
        public readonly ?int $performedByEmployeeId,
        public readonly ?string $performedBy,
        public readonly ?string $nextServiceDate,
        public readonly ?string $notes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'asset_id' => $this->assetId,
            'maintenance_date' => $this->maintenanceDate,
            'type' => $this->type,
            'cost' => $this->cost !== null ? number_format($this->cost, 2, '.', '') : null,
            'performed_by' => $this->performedBy,
            'performed_by_employee_id' => $this->performedByEmployeeId,
            'next_service_date' => $this->nextServiceDate,
            'notes' => $this->notes,
        ];
    }
}

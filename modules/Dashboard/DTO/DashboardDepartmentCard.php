<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard\DTO;

final class DashboardDepartmentCard
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly string $status,
        public readonly int $employeeCount,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            name: (string) ($row['name'] ?? ''),
            code: (string) ($row['code'] ?? ''),
            status: (string) ($row['status'] ?? 'ACTIVE'),
            employeeCount: (int) ($row['employee_count'] ?? 0),
        );
    }
}

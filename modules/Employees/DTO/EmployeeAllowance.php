<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\DTO;

final class EmployeeAllowance
{
    public function __construct(
        public readonly int $id,
        public readonly int $employeeId,
        public readonly string $name,
        public readonly float $amount,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['employee_id'],
            (string) $row['name'],
            (float) $row['amount'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employeeId,
            'name' => $this->name,
            'amount' => $this->amount,
        ];
    }
}

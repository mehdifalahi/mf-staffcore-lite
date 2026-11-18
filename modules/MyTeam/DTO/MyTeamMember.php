<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyTeam\DTO;

final class MyTeamMember
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $photo,
        public readonly string $departmentName,
        public readonly string $positionTitle,
        public readonly string $employmentStatus,
        public readonly ?string $hireDate,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            firstName: (string) ($row['first_name'] ?? ''),
            lastName: (string) ($row['last_name'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            photo: isset($row['photo']) && $row['photo'] !== '' ? (string) $row['photo'] : null,
            departmentName: (string) ($row['department_name'] ?? ''),
            positionTitle: (string) ($row['position_title'] ?? ''),
            employmentStatus: (string) ($row['employment_status'] ?? ''),
            hireDate: isset($row['hire_date']) && $row['hire_date'] !== '' ? (string) $row['hire_date'] : null,
        );
    }

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\DTO;

final class Employee
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_ON_LEAVE = 'ON_LEAVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_RESIGNED = 'RESIGNED';
    public const STATUS_TERMINATED = 'TERMINATED';

    public const TYPE_FULLTIME = 'FULLTIME';
    public const TYPE_PARTTIME = 'PARTTIME';
    public const TYPE_CONTRACT = 'CONTRACT';
    public const TYPE_TEMPORARY = 'TEMPORARY';
    public const TYPE_INTERN = 'INTERN';
    public const TYPE_FREELANCE = 'FREELANCE';

    /**
     * @param list<EmployeeAllowance> $allowances
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly int $departmentId,
        public readonly int $positionId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?int $userId,
        public readonly ?string $birthDate,
        public readonly string $hireDate,
        public readonly string $employmentStatus,
        public readonly string $employmentType,
        public readonly ?float $basicSalary,
        public readonly int $annualLeave,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $province,
        public readonly ?string $country,
        public readonly ?string $zipCode,
        public readonly ?string $photo,
        public readonly ?string $notes,
        public readonly ?string $departmentName,
        public readonly ?string $positionTitle,
        public readonly ?string $positionLevel = null,
        public readonly int $remainingLeave = 0,
        public readonly array $allowances = [],
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['code'],
            (int) $row['department_id'],
            (int) $row['position_id'],
            (string) $row['first_name'],
            (string) $row['last_name'],
            (string) $row['email'],
            isset($row['phone']) && $row['phone'] !== '' ? (string) $row['phone'] : null,
            isset($row['user_id']) && $row['user_id'] !== null && $row['user_id'] !== ''
                ? (int) $row['user_id']
                : null,
            isset($row['birth_date']) && $row['birth_date'] !== null && $row['birth_date'] !== ''
                ? (string) $row['birth_date']
                : null,
            (string) $row['hire_date'],
            (string) $row['employment_status'],
            (string) $row['employment_type'],
            isset($row['basic_salary']) && $row['basic_salary'] !== null && $row['basic_salary'] !== ''
                ? (float) $row['basic_salary']
                : null,
            (int) ($row['annual_leave'] ?? 0),
            isset($row['address']) && $row['address'] !== '' ? (string) $row['address'] : null,
            isset($row['city']) && $row['city'] !== '' ? (string) $row['city'] : null,
            isset($row['province']) && $row['province'] !== '' ? (string) $row['province'] : null,
            isset($row['country']) && $row['country'] !== '' ? (string) $row['country'] : null,
            isset($row['zip_code']) && $row['zip_code'] !== '' ? (string) $row['zip_code'] : null,
            isset($row['photo']) && $row['photo'] !== '' ? (string) $row['photo'] : null,
            isset($row['notes']) && $row['notes'] !== '' ? (string) $row['notes'] : null,
            isset($row['department_name']) ? (string) $row['department_name'] : null,
            isset($row['position_title']) ? (string) $row['position_title'] : null,
            isset($row['position_level']) && $row['position_level'] !== ''
                ? (string) $row['position_level']
                : null,
            self::remainingLeaveFromRow($row),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function remainingLeaveFromRow(array $row): int
    {
        $annual = (int) ($row['annual_leave'] ?? 0);
        $used = (int) ($row['used_leave_days'] ?? 0);

        return max(0, $annual - $used);
    }

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'department_id' => $this->departmentId,
            'position_id' => $this->positionId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'user_id' => $this->userId,
            'birth_date' => $this->birthDate,
            'hire_date' => $this->hireDate,
            'employment_status' => $this->employmentStatus,
            'employment_status_label' => self::statusLabel($this->employmentStatus),
            'employment_type' => $this->employmentType,
            'employment_type_label' => self::typeLabel($this->employmentType),
            'basic_salary' => $this->basicSalary,
            'annual_leave' => $this->annualLeave,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'zip_code' => $this->zipCode,
            'photo' => $this->photo,
            'notes' => $this->notes,
            'department_name' => $this->departmentName,
            'position_title' => $this->positionTitle,
            'position_level' => $this->positionLevel,
            'position_level_label' => self::positionLevelLabel($this->positionLevel),
            'remaining_leave' => $this->remainingLeave,
            'allowances' => array_map(
                static fn (EmployeeAllowance $a): array => $a->toArray(),
                $this->allowances,
            ),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_LEAVE => 'On Leave',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_RESIGNED => 'Resigned',
            self::STATUS_TERMINATED => 'Terminated',
            default => $status,
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_FULLTIME => 'Full Time',
            self::TYPE_PARTTIME => 'Part Time',
            self::TYPE_CONTRACT => 'Contract',
            self::TYPE_TEMPORARY => 'Temporary',
            self::TYPE_INTERN => 'Intern',
            self::TYPE_FREELANCE => 'Freelance',
            default => $type,
        };
    }

    public static function positionLevelLabel(?string $level): string
    {
        if ($level === null || $level === '') {
            return '';
        }

        return match (strtoupper($level)) {
            'ENTRY' => 'Entry',
            'JUNIOR' => 'Junior',
            'MID' => 'Mid',
            'SENIOR' => 'Senior',
            'LEAD' => 'Lead',
            'MANAGER' => 'Manager',
            'DIRECTOR' => 'Director',
            'EXECUTIVE' => 'Executive',
            default => ucfirst(strtolower($level)),
        };
    }
}

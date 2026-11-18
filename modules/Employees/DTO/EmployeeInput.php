<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\DTO;

final class EmployeeInput
{
    /**
     * @param list<array{name: string, amount: float}> $allowances
     */
    public function __construct(
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
        public readonly array $allowances = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'code' => $this->code,
            'department_id' => $this->departmentId,
            'position_id' => $this->positionId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_id' => $this->userId,
            'birth_date' => $this->birthDate,
            'hire_date' => $this->hireDate,
            'employment_status' => $this->employmentStatus,
            'employment_type' => $this->employmentType,
            'basic_salary' => $this->basicSalary !== null
                ? number_format($this->basicSalary, 2, '.', '')
                : null,
            'annual_leave' => $this->annualLeave,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'zip_code' => $this->zipCode,
            'photo' => $this->photo,
            'notes' => $this->notes,
        ];
    }
}

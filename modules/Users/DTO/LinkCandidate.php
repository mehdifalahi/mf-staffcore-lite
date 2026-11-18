<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

final class LinkCandidate
{
    public function __construct(
        public readonly int $platformUserId,
        public readonly string $login,
        public readonly string $email,
        public readonly string $displayName,
        public readonly bool $hasEmployee,
        public readonly ?int $employeeId = null,
        public readonly ?string $departmentName = null,
        public readonly ?string $platformRoleLabel = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform_user_id' => $this->platformUserId,
            'login' => $this->login,
            'email' => $this->email,
            'display_name' => $this->displayName,
            'has_employee' => $this->hasEmployee,
            'employee_id' => $this->employeeId,
            'department_name' => $this->departmentName,
            'platform_role_label' => $this->platformRoleLabel,
        ];
    }
}

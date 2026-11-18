<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

use MF\StaffCore\Authorization\ManagerScopeModeRegistry;

final class StaffUserInput
{
    public function __construct(
        public readonly string $role,
        public readonly ?string $phone,
        public readonly string $status,
        public readonly string $managerScopeMode = ManagerScopeModeRegistry::AUTO,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'role' => $this->role,
            'manager_scope_mode' => $this->managerScopeMode,
            'phone' => $this->phone,
            'status' => $this->status,
        ];
    }
}

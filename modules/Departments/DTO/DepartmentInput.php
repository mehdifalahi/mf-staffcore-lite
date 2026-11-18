<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Departments\DTO;

final class DepartmentInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly ?int $managerId,
        public readonly string $status,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'manager_id' => $this->managerId,
            'status' => $this->status,
        ];
    }
}

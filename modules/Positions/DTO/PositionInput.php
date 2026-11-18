<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Positions\DTO;

final class PositionInput
{
    public function __construct(
        public readonly string $title,
        public readonly string $code,
        public readonly int $departmentId,
        public readonly string $level,
        public readonly ?string $minSalary,
        public readonly ?string $maxSalary,
        public readonly ?string $description,
        public readonly string $status,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'title' => $this->title,
            'code' => $this->code,
            'department_id' => $this->departmentId,
            'level' => $this->level,
            'min_salary' => $this->minSalary,
            'max_salary' => $this->maxSalary,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Positions\DTO;

final class Position
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const LEVEL_ENTRY = 'ENTRY';
    public const LEVEL_JUNIOR = 'JUNIOR';
    public const LEVEL_MID = 'MID';
    public const LEVEL_SENIOR = 'SENIOR';
    public const LEVEL_LEAD = 'LEAD';
    public const LEVEL_MANAGER = 'MANAGER';
    public const LEVEL_DIRECTOR = 'DIRECTOR';
    public const LEVEL_EXECUTIVE = 'EXECUTIVE';

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $code,
        public readonly int $departmentId,
        public readonly string $level,
        public readonly ?string $minSalary,
        public readonly ?string $maxSalary,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $departmentName = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            title: (string) ($row['title'] ?? ''),
            code: (string) ($row['code'] ?? ''),
            departmentId: (int) ($row['department_id'] ?? 0),
            level: (string) ($row['level'] ?? self::LEVEL_MID),
            minSalary: self::nullableDecimal($row['min_salary'] ?? null),
            maxSalary: self::nullableDecimal($row['max_salary'] ?? null),
            description: self::nullableString($row['description'] ?? null),
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            departmentName: self::nullableString($row['department_name'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'department_id' => $this->departmentId,
            'level' => $this->level,
            'min_salary' => $this->minSalary,
            'max_salary' => $this->maxSalary,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'department_name' => $this->departmentName,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

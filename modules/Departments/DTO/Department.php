<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Departments\DTO;

final class Department
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly ?int $managerId,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $managerName = null,
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
            description: self::nullableString($row['description'] ?? null),
            managerId: isset($row['manager_id']) && $row['manager_id'] !== '' && $row['manager_id'] !== null
                ? (int) $row['manager_id']
                : null,
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            managerName: self::nullableString($row['manager_name'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'manager_id' => $this->managerId,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'manager_name' => $this->managerName,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

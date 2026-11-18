<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

use MF\StaffCore\Authorization\ManagerScopeModeRegistry;

final class StaffUser
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function __construct(
        public readonly int $id,
        public readonly int $platformUserId,
        public readonly string $role,
        public readonly string $managerScopeMode,
        public readonly ?string $phone,
        public readonly string $status,
        public readonly int $failedLoginAttempts,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $displayName = null,
        public readonly ?string $email = null,
        public readonly ?string $login = null,
    ) {
    }

    public function isProtected(): bool
    {
        return $this->platformUserId === 1;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $mode = (string) ($row['manager_scope_mode'] ?? ManagerScopeModeRegistry::AUTO);

        if (! ManagerScopeModeRegistry::isValid($mode)) {
            $mode = ManagerScopeModeRegistry::AUTO;
        }

        return new self(
            id: (int) ($row['id'] ?? 0),
            platformUserId: (int) ($row['platform_user_id'] ?? 0),
            role: (string) ($row['role'] ?? ''),
            managerScopeMode: $mode,
            phone: self::nullableString($row['phone'] ?? null),
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            failedLoginAttempts: (int) ($row['failed_login_attempts'] ?? 0),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            displayName: self::nullableString($row['display_name'] ?? null),
            email: self::nullableString($row['email'] ?? null),
            login: self::nullableString($row['login'] ?? null),
        );
    }

    public function withPlatformData(string $displayName, string $email, string $login): self
    {
        return new self(
            id: $this->id,
            platformUserId: $this->platformUserId,
            role: $this->role,
            managerScopeMode: $this->managerScopeMode,
            phone: $this->phone,
            status: $this->status,
            failedLoginAttempts: $this->failedLoginAttempts,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            createdBy: $this->createdBy,
            updatedBy: $this->updatedBy,
            displayName: $displayName,
            email: $email,
            login: $login,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'platform_user_id' => $this->platformUserId,
            'role' => $this->role,
            'manager_scope_mode' => $this->managerScopeMode,
            'phone' => $this->phone,
            'status' => $this->status,
            'failed_login_attempts' => $this->failedLoginAttempts,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'display_name' => $this->displayName,
            'email' => $this->email,
            'login' => $this->login,
            'is_protected' => $this->isProtected(),
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

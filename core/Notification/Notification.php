<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

final class Notification
{
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    public function __construct(
        public readonly int $id,
        public readonly int $recipientStaffUserId,
        public readonly string $eventKey,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $entityType,
        public readonly ?int $entityId,
        public readonly ?string $actionUrl,
        public readonly string $priority,
        public readonly ?string $readAt,
        public readonly string $createdAt,
    ) {
    }

    public function isUnread(): bool
    {
        return $this->readAt === null || $this->readAt === '';
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            recipientStaffUserId: (int) ($row['recipient_staff_user_id'] ?? 0),
            eventKey: (string) ($row['event_key'] ?? ''),
            title: (string) ($row['title'] ?? ''),
            body: self::nullableString($row['body'] ?? null),
            entityType: self::nullableString($row['entity_type'] ?? null),
            entityId: isset($row['entity_id']) && $row['entity_id'] !== null && $row['entity_id'] !== ''
                ? (int) $row['entity_id']
                : null,
            actionUrl: self::nullableString($row['action_url'] ?? null),
            priority: (string) ($row['priority'] ?? self::PRIORITY_NORMAL),
            readAt: self::nullableString($row['read_at'] ?? null),
            createdAt: (string) ($row['created_at'] ?? ''),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

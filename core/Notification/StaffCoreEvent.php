<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

final class StaffCoreEvent
{
    public const RECIPIENT_EMPLOYEE = 'EMP';
    public const RECIPIENT_MANAGER = 'MGR';
    public const RECIPIENT_HR = 'HR';
    public const RECIPIENT_ADMIN = 'ADM';
    public const RECIPIENT_ACTOR = 'ACTOR';
    public const RECIPIENT_STAFF_USER = 'STAFF_USER';

    /**
     * @param list<string> $recipientCodes
     * @param array<string, mixed> $context
     */
    public function __construct(
        public readonly string $eventKey,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $entityType,
        public readonly ?int $entityId,
        public readonly ?string $actionUrl,
        public readonly string $priority,
        public readonly array $recipientCodes,
        public readonly array $context = [],
        public readonly ?int $actorPlatformUserId = null,
        public readonly bool $includeActor = false,
    ) {
    }
}

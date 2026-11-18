<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

use MF\StaffCore\Support\PaginatedList;

interface NotificationRepository
{
    /**
     * @param list<int> $recipientStaffUserIds
     */
    public function createMany(
        array $recipientStaffUserIds,
        string $eventKey,
        string $title,
        ?string $body,
        ?string $entityType,
        ?int $entityId,
        ?string $actionUrl,
        string $priority,
    ): void;

    /**
     * @return list<Notification>
     */
    public function listForRecipient(
        int $recipientStaffUserId,
        int $limit,
        bool $unreadOnly = false,
    ): array;

    /**
     * @return PaginatedList<Notification>
     */
    public function listPageForRecipient(
        int $recipientStaffUserId,
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
    ): PaginatedList;

    public function countUnreadForRecipient(int $recipientStaffUserId): int;

    public function markRead(int $id, int $recipientStaffUserId): bool;

    public function markAllRead(int $recipientStaffUserId): int;
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

use MF\StaffCore\Support\PaginatedList;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $repository,
    ) {
    }

    public function unreadCount(int $staffUserId): int
    {
        if ($staffUserId <= 0) {
            return 0;
        }

        return $this->repository->countUnreadForRecipient($staffUserId);
    }

    /**
     * @return list<Notification>
     */
    public function listForRecipient(
        int $staffUserId,
        int $limit = 8,
        bool $unreadOnly = false,
    ): array {
        if ($staffUserId <= 0) {
            return [];
        }

        return $this->repository->listForRecipient($staffUserId, $limit, $unreadOnly);
    }

    /**
     * @return PaginatedList<Notification>
     */
    public function listPageForRecipient(
        int $staffUserId,
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
    ): PaginatedList {
        return $this->repository->listPageForRecipient($staffUserId, $page, $perPage);
    }

    public function markRead(int $notificationId, int $staffUserId): bool
    {
        if ($notificationId <= 0 || $staffUserId <= 0) {
            return false;
        }

        return $this->repository->markRead($notificationId, $staffUserId);
    }

    public function markAllRead(int $staffUserId): int
    {
        if ($staffUserId <= 0) {
            return 0;
        }

        return $this->repository->markAllRead($staffUserId);
    }
}

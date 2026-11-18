<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Notifications\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Notification\NotificationRepository as NotificationRepositoryContract;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class NotificationRepository implements NotificationRepositoryContract
{
    private const TABLE = 'notification';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    public function createMany(
        array $recipientStaffUserIds,
        string $eventKey,
        string $title,
        ?string $body,
        ?string $entityType,
        ?int $entityId,
        ?string $actionUrl,
        string $priority,
    ): void {
        $now = gmdate('Y-m-d H:i:s');

        foreach ($recipientStaffUserIds as $recipientStaffUserId) {
            $id = $this->idSequence->nextId(self::TABLE);
            $ok = $this->db->insert(self::TABLE, [
                'id' => $id,
                'recipient_staff_user_id' => $recipientStaffUserId,
                'event_key' => $eventKey,
                'title' => $title,
                'body' => $body,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action_url' => $actionUrl,
                'priority' => $priority,
                'read_at' => null,
                'created_at' => $now,
            ]);

            if (! $ok) {
                throw new RuntimeException(esc_html('Failed to create notification: ' . $this->db->getLastError()));
            }
        }
    }

    public function listForRecipient(
        int $recipientStaffUserId,
        int $limit,
        bool $unreadOnly = false,
    ): array {
        $table = $this->db->resolveTableName(self::TABLE);
        $limit = max(1, $limit);
        $unreadSql = $unreadOnly ? ' AND read_at IS NULL' : '';

        $rows = $this->db->getResults(
            'SELECT id, recipient_staff_user_id, event_key, title, body, entity_type, entity_id, action_url,
                priority, read_at, created_at
             FROM `' . $table . '`
             WHERE recipient_staff_user_id = %d' . $unreadSql . '
             ORDER BY id DESC
             LIMIT %d',
            [$recipientStaffUserId, $limit],
        );

        return array_map(
            static fn (array $row): Notification => Notification::fromRow($row),
            $rows,
        );
    }

    public function listPageForRecipient(
        int $recipientStaffUserId,
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
    ): PaginatedList {
        $table = $this->db->resolveTableName(self::TABLE);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*)
             FROM `' . $table . '`
             WHERE recipient_staff_user_id = %d',
            [$recipientStaffUserId],
        );

        $rows = $this->db->getResults(
            'SELECT id, recipient_staff_user_id, event_key, title, body, entity_type, entity_id, action_url,
                priority, read_at, created_at
             FROM `' . $table . '`
             WHERE recipient_staff_user_id = %d
             ORDER BY id DESC
             LIMIT %d OFFSET %d',
            [$recipientStaffUserId, $perPage, $offset],
        );

        $items = array_map(
            static fn (array $row): Notification => Notification::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    public function countUnreadForRecipient(int $recipientStaffUserId): int
    {
        $table = $this->db->resolveTableName(self::TABLE);

        return (int) $this->db->getVar(
            'SELECT COUNT(*)
             FROM `' . $table . '`
             WHERE recipient_staff_user_id = %d AND read_at IS NULL',
            [$recipientStaffUserId],
        );
    }

    public function markRead(int $id, int $recipientStaffUserId): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $exists = (int) $this->db->getVar(
            'SELECT COUNT(*)
             FROM `' . $table . '`
             WHERE id = %d AND recipient_staff_user_id = %d',
            [$id, $recipientStaffUserId],
        );

        if ($exists <= 0) {
            return false;
        }

        return $this->db->update(
            self::TABLE,
            ['read_at' => gmdate('Y-m-d H:i:s')],
            ['id' => $id, 'recipient_staff_user_id' => $recipientStaffUserId],
        );
    }

    public function markAllRead(int $recipientStaffUserId): int
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT id
             FROM `' . $table . '`
             WHERE recipient_staff_user_id = %d AND read_at IS NULL',
            [$recipientStaffUserId],
        );

        $updated = 0;
        $now = gmdate('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $ok = $this->db->update(
                self::TABLE,
                ['read_at' => $now],
                ['id' => $id, 'recipient_staff_user_id' => $recipientStaffUserId],
            );

            if ($ok) {
                $updated++;
            }
        }

        return $updated;
    }
}


<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\NotificationsPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Platforms\WordPress\Navigation\NotificationActionUrlResolver;

final class NotificationAjaxHandler
{
    use StaffCoreAjaxAuthorization;

    private const NONCE_ACTION = 'mf_staffcore_notifications';
    private const MODULE = 'notifications';

    public function unreadCount(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $staffUserId = $this->currentStaffUserId();
        $unread = $this->service()->unreadCount($staffUserId);

        wp_send_json_success([
            'unread' => $unread,
        ]);
    }

    public function dropdown(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $staffUserId = $this->currentStaffUserId();
        $items = $this->service()->listForRecipient($staffUserId, 8, true);

        wp_send_json_success([
            'notifications' => array_map([$this, 'toPayload'], $items),
        ]);
    }

    public function inboxList(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $page = max(1, $page);
        $perPage = \MF\StaffCore\Support\PaginatedList::DEFAULT_PER_PAGE;
        $staffUserId = $this->currentStaffUserId();
        $result = $this->service()->listPageForRecipient($staffUserId, $page, $perPage);

        wp_send_json_success([
            'items' => array_map([$this, 'toPayload'], $result->items),
            'page' => $result->page,
            'total' => $result->total,
            'total_pages' => $result->totalPages(),
            'per_page' => $result->perPage,
        ]);
    }

    public function markRead(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            wp_send_json_error(['message' => __('Invalid notification.', 'mf-staffcore-lite')], 400);
        }

        $staffUserId = $this->currentStaffUserId();
        $ok = $this->service()->markRead($id, $staffUserId);

        if (! $ok) {
            wp_send_json_error(['message' => __('Notification not found.', 'mf-staffcore-lite')], 404);
        }

        wp_send_json_success([
            'unread' => $this->service()->unreadCount($staffUserId),
        ]);
    }

    public function markAllRead(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $staffUserId = $this->currentStaffUserId();
        $updated = $this->service()->markAllRead($staffUserId);

        wp_send_json_success([
            'updated' => $updated,
            'unread' => $this->service()->unreadCount($staffUserId),
        ]);
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(Notification $notification): array
    {
        $resolver = new NotificationActionUrlResolver();
        $frontendPanel = NotificationActionUrlResolver::isFrontendPanelAjaxRequest();

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'event_key' => $notification->eventKey,
            'priority' => $notification->priority,
            'tone' => NotificationsPresenter::priorityDotTone($notification),
            'action_url' => $resolver->resolve($notification->actionUrl, $frontendPanel),
            'is_unread' => $notification->isUnread(),
            'created_at' => $notification->createdAt,
            'relative_time' => NotificationsPresenter::relativeTime($notification->createdAt),
        ];
    }

    private function currentStaffUserId(): int
    {
        return ServiceContainer::instance()->authorization()->current()?->staffUserId ?? 0;
    }

    private function service(): \MF\StaffCore\Notification\NotificationService
    {
        return ServiceContainer::instance()->notifications();
    }
}

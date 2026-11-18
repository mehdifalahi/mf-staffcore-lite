<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Notification\Notification> $notifications
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var string $ajaxUrl
 * @var string $nonce
 * @var \MF\StaffCore\Platforms\WordPress\Navigation\NotificationActionUrlResolver $actionUrlResolver
 * @var string $panelContext
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\NotificationsPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div
    class="staffcore-notifications-inbox"
    data-staffcore-module="notifications-inbox"
    data-panel-context="<?php echo esc_attr($panelContext); ?>"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
>
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1"><?php esc_html_e('Inbox', 'mf-staffcore-lite'); ?></h3>
                <p class="text-secondary mb-0 small"><?php esc_html_e('All notifications addressed to your account.', 'mf-staffcore-lite'); ?></p>
            </div>
            <div class="card-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="mark-all-read">
                    <?php TablerIcon::print('checks', 'me-1'); ?>
                    <?php esc_html_e('Mark all read', 'mf-staffcore-lite'); ?>
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th class="w-1"></th>
                        <th><?php esc_html_e('Notification', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Event', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Received', 'mf-staffcore-lite'); ?></th>
                        <th class="w-1"><?php esc_html_e('Actions', 'mf-staffcore-lite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($notifications === []) : ?>
                        <tr>
                            <td colspan="5" class="text-secondary text-center py-4">
                                <?php esc_html_e('No notifications yet.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($notifications as $notification) : ?>
                            <tr data-notification-id="<?php echo esc_attr((string) $notification->id); ?>" class="<?php echo $notification->isUnread() ? 'staffcore-notification-unread' : ''; ?>">
                                <td>
                                    <span class="badge bg-<?php echo esc_attr(NotificationsPresenter::priorityDotTone($notification)); ?>-lt staffcore-notification-dot"></span>
                                </td>
                                <td>
                                    <div class="<?php echo $notification->isUnread() ? 'fw-semibold' : ''; ?>">
                                        <?php echo esc_html($notification->title); ?>
                                    </div>
                                    <?php if ($notification->body !== null && $notification->body !== '') : ?>
                                        <div class="text-secondary small">
                                            <?php echo esc_html($notification->body); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary"><?php echo esc_html($notification->eventKey); ?></td>
                                <td>
                                    <div><?php echo esc_html(NotificationsPresenter::relativeTime($notification->createdAt)); ?></div>
                                    <div class="text-secondary small"><?php echo esc_html(gmdate('Y-m-d H:i', strtotime($notification->createdAt) ?: time())); ?></div>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($notification->isUnread()) : ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-action="mark-read" data-id="<?php echo esc_attr((string) $notification->id); ?>">
                                                <?php TablerIcon::print('check'); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php
                                        $resolvedActionUrl = NotificationsPresenter::resolvedActionUrl(
                                            $notification,
                                            $actionUrlResolver,
                                            $panelContext === 'frontend',
                                        );
                                        ?>
                                        <?php if ($resolvedActionUrl !== null && $resolvedActionUrl !== '') : ?>
                                            <a
                                                class="btn btn-sm btn-outline-primary"
                                                href="<?php echo esc_url($resolvedActionUrl); ?>"
                                                data-action="open-link"
                                                data-id="<?php echo esc_attr((string) $notification->id); ?>"
                                            >
                                                <?php TablerIcon::print('arrow-up-right'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $pageUrl = static fn (int $page): string => $pagination->pageUrl($page);
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>
</div>

<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Platforms\WordPress\Navigation\NotificationActionUrlResolver;

final class NotificationsPresenter
{
    public function render(): void
    {
        $container = ServiceContainer::instance();
        $notificationService = $container->notifications();
        $staffUserId = $container->authorization()->current()?->staffUserId ?? 0;

        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $notificationService->listPageForRecipient($staffUserId, $page, $perPage);

        $page = $pagination->redirectIfPageOutOfRange($page, $result);
        if ($page !== $result->page) {
            $result = $notificationService->listPageForRecipient($staffUserId, $page, $perPage);
        }

        $notifications = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = \MF\StaffCore\Platforms\WordPress\Ajax\NotificationAjaxHandler::nonce();
        $actionUrlResolver = new NotificationActionUrlResolver();
        $panelContext = $urlBuilder->isFrontendContext() ? 'frontend' : 'admin';

        include MF_STAFFCORE_PATH . 'modules/Notifications/Views/page.php';
    }

    public static function resolvedActionUrl(
        Notification $notification,
        NotificationActionUrlResolver $resolver,
        bool $frontendPanel,
    ): ?string {
        return $resolver->resolve($notification->actionUrl, $frontendPanel);
    }

    public static function relativeTime(string $createdAt): string
    {
        $timestamp = strtotime($createdAt);
        if ($timestamp === false || ! function_exists('human_time_diff')) {
            return '';
        }

        return sprintf(
            /* translators: %s: human-readable time difference */
            __('%s ago', 'mf-staffcore-lite'),
            human_time_diff($timestamp, (int) current_time('timestamp')),
        );
    }

    public static function priorityDotTone(Notification $notification): string
    {
        return match ($notification->priority) {
            Notification::PRIORITY_HIGH => 'red',
            Notification::PRIORITY_LOW => 'secondary',
            default => 'azure',
        };
    }
}

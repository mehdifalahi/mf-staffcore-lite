<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var string $pageDescription
 * @var string $contentView
 * @var list<array<string, mixed>> $menus
 * @var string $dashboardUrl
 * @var string $activeMenuId
 * @var string|null $activeTabSlug
 * @var list<array<string, mixed>> $menuTabs
 * @var \MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder $urlBuilder
 * @var \MF\StaffCore\Navigation\AdminPageResolver $resolver
 * @var string $pageHeaderIcon
 * @var string $wordpressAdminUrl
 * @var list<array{label: string, value: int|string, icon: string, tone: string}> $statCards
 * @var bool $showUpgradeUi
 * @var string $editionLabel
 * @var string $sidebarUpgradeUrl
 */

if (! defined('ABSPATH')) {
    exit;
}

$notificationAjaxUrl = admin_url('admin-ajax.php');
$notificationNonce = \MF\StaffCore\Platforms\WordPress\Ajax\NotificationAjaxHandler::nonce();
$notificationsInboxUrl = $urlBuilder->build(
    new \MF\StaffCore\Navigation\AdminRoute('notifications', null),
);
$panelContext = $urlBuilder->isFrontendContext() ? 'frontend' : 'admin';
?>
<div
    class="page staffcore-admin-wrap"
    id="staffcore-app"
    data-bs-theme="dark"
    data-staffcore-notifications-shell
    data-panel-context="<?php echo esc_attr($panelContext); ?>"
    data-ajax-url="<?php echo esc_url($notificationAjaxUrl); ?>"
    data-nonce="<?php echo esc_attr($notificationNonce); ?>"
>
    <?php
    // Theme FOUC init: registered in AssetRegistrar via wp_add_inline_script.
    if (function_exists('wp_scripts') && wp_script_is('mf-staffcore-theme-init', 'enqueued')) {
        wp_print_scripts('mf-staffcore-theme-init');
    }
    ?>
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="<?php echo esc_url($dashboardUrl); ?>">
                    <?php esc_html_e('MF StaffCore', 'mf-staffcore-lite'); ?>
                </a>
            </h1>
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <?php include MF_STAFFCORE_PATH . 'ui/partials/sidebar-nav-items.php'; ?>
                <?php if (! $urlBuilder->isFrontendContext()) : ?>
                    <div class="staffcore-sidebar-back">
                        <a
                            href="<?php echo esc_url($wordpressAdminUrl); ?>"
                            class="btn btn-sm btn-outline-secondary staffcore-back-wp"
                        >
                            <?php \MF\StaffCore\UI\TablerIcon::print('arrow-left'); ?>
                            <span><?php esc_html_e('WordPress Dashboard', 'mf-staffcore-lite'); ?></span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="staffcore-sidebar-spacer" aria-hidden="true"></div>
            <div class="staffcore-sidebar-footer">
                <?php include MF_STAFFCORE_PATH . 'ui/partials/sidebar-plan-upgrade.php'; ?>
                <button
                    type="button"
                    class="staffcore-theme-switch"
                    data-staffcore-theme-toggle
                    aria-label="<?php esc_attr_e('Toggle light or dark theme', 'mf-staffcore-lite'); ?>"
                    aria-pressed="false"
                >
                    <span class="staffcore-theme-switch-track">
                        <span class="staffcore-theme-switch-thumb">
                            <?php \MF\StaffCore\UI\TablerIcon::print('moon', 'staffcore-theme-icon staffcore-theme-icon-moon'); ?>
                            <?php \MF\StaffCore\UI\TablerIcon::print('sun', 'staffcore-theme-icon staffcore-theme-icon-sun'); ?>
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </aside>
    <?php include MF_STAFFCORE_PATH . 'ui/partials/mobile-nav.php'; ?>
    <div class="page-wrapper">
        <div id="staffcore-wp-admin-notices" class="staffcore-wp-admin-notices d-print-none" aria-live="polite">
            <div class="container-fluid">
                <?php do_action('mf_staffcore_admin_notices'); ?>
            </div>
        </div>
        <?php if (($statCards ?? []) !== []) : ?>
            <div class="staffcore-performance-stats-wrap d-print-none">
                <div class="container-fluid pt-3 pb-2">
                    <?php include MF_STAFFCORE_PATH . 'ui/components/stat-cards-outline.php'; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="page-header d-print-none">
            <div class="container-fluid">
                <div class="row g-2 align-items-center">
                    <div class="col-auto d-lg-none">
                        <button
                            type="button"
                            class="btn btn-icon btn-outline-secondary staffcore-mobile-nav-toggle"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#staffcore-mobile-nav"
                            aria-controls="staffcore-mobile-nav"
                            aria-label="<?php esc_attr_e('Open menu', 'mf-staffcore-lite'); ?>"
                        >
                            <?php \MF\StaffCore\UI\TablerIcon::print('menu-2'); ?>
                        </button>
                    </div>
                    <div class="col">
                        <?php include MF_STAFFCORE_PATH . 'ui/partials/page-heading.php'; ?>
                    </div>
                    <div class="col-auto">
                        <div class="staffcore-notifications-nav dropdown">
                            <a
                                href="#"
                                class="btn btn-outline-secondary btn-icon position-relative"
                                data-bs-toggle="dropdown"
                                data-action="open-notifications-dropdown"
                                aria-label="<?php esc_attr_e('Notifications', 'mf-staffcore-lite'); ?>"
                            >
                                <?php \MF\StaffCore\UI\TablerIcon::print('bell'); ?>
                                <span class="badge bg-red badge-notification badge-pill d-none" data-role="notification-unread-badge"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow dropdown-menu-card staffcore-notifications-dropdown">
                                <div class="card">
                                    <div class="card-header d-flex align-items-center">
                                        <h3 class="card-title mb-0"><?php esc_html_e('Notifications', 'mf-staffcore-lite'); ?></h3>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-action="mark-all-read">
                                            <?php esc_html_e('Mark all read', 'mf-staffcore-lite'); ?>
                                        </button>
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable" data-role="notification-dropdown-list">
                                        <div class="list-group-item text-secondary text-center py-3">
                                            <?php esc_html_e('No new notifications', 'mf-staffcore-lite'); ?>
                                        </div>
                                    </div>
                                    <div class="card-body text-center border-top py-2">
                                        <a href="<?php echo esc_url($notificationsInboxUrl); ?>" class="btn btn-outline-primary btn-sm">
                                            <?php esc_html_e('View all', 'mf-staffcore-lite'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/partials/horizontal-tabs.php'; ?>
            </div>
        </div>
        <div class="page-body">
            <div class="container-fluid">
                <?php
                if (is_readable($contentView)) {
                    include $contentView;
                } else {
                    echo '<div class="alert alert-warning">';
                    esc_html_e('Page view not found.', 'mf-staffcore-lite');
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

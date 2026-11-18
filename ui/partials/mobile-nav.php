<?php

declare(strict_types=1);

/**
 * Mobile navigation: Tabler offcanvas drawer (visible below lg breakpoint only).
 *
 * @var list<array<string, mixed>> $menus
 * @var string $dashboardUrl
 * @var string $activeMenuId
 * @var string|null $activeTabSlug
 * @var \MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder $urlBuilder
 * @var \MF\StaffCore\Navigation\AdminPageResolver $resolver
 * @var string $wordpressAdminUrl
 * @var bool $showUpgradeUi
 * @var string $editionLabel
 * @var string $sidebarUpgradeUrl
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div
    class="offcanvas offcanvas-start staffcore-mobile-nav d-lg-none"
    tabindex="-1"
    id="staffcore-mobile-nav"
    aria-labelledby="staffcore-mobile-nav-label"
    data-staffcore-mobile-nav
>
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title mb-0" id="staffcore-mobile-nav-label">
            <a href="<?php echo esc_url($dashboardUrl); ?>" class="staffcore-mobile-nav-brand">
                <?php esc_html_e('MF StaffCore', 'mf-staffcore-lite'); ?>
            </a>
        </h2>
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="<?php esc_attr_e('Close menu', 'mf-staffcore-lite'); ?>"
        ></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div class="staffcore-mobile-nav-menu flex-grow-1 overflow-auto px-2 py-2">
            <?php
            $navListClass = '';
            include MF_STAFFCORE_PATH . 'ui/partials/sidebar-nav-items.php';
            ?>
        </div>
        <div class="staffcore-mobile-nav-footer border-top px-3 py-3">
            <?php if (! $urlBuilder->isFrontendContext()) : ?>
                <a
                    href="<?php echo esc_url($wordpressAdminUrl); ?>"
                    class="btn btn-sm btn-outline-secondary staffcore-back-wp w-100 mb-3"
                >
                    <?php \MF\StaffCore\UI\TablerIcon::print('arrow-left'); ?>
                    <span><?php esc_html_e('WordPress Dashboard', 'mf-staffcore-lite'); ?></span>
                </a>
            <?php endif; ?>
            <?php include MF_STAFFCORE_PATH . 'ui/partials/sidebar-plan-upgrade.php'; ?>
            <div class="d-flex justify-content-center">
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
    </div>
</div>

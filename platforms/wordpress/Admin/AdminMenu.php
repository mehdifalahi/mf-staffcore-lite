<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin;

use MF\StaffCore\Navigation\AdminPageResolver;

final class AdminMenu
{
    /**
     * WordPress menu capability only (any logged-in user with read).
     * StaffCore authorization is enforced in {@see PageRenderer}.
     */
    public const CAPABILITY = 'read';

    public function __construct(
        private readonly PageRenderer $pageRenderer,
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_init', [$this, 'disableThirdPartyAdminNotices']);
        add_filter('admin_body_class', [$this, 'adminBodyClass']);
    }

    public function disableThirdPartyAdminNotices(): void
    {
        if (! $this->isStaffCoreScreen()) {
            return;
        }

        // Hello Dolly (wp-content/plugins/hello.php) outputs #dolly with float:right.
        remove_action('admin_notices', 'hello_dolly');
    }

    public function registerMenus(): void
    {
        add_menu_page(
            __('StaffCore', 'mf-staffcore-lite'),
            __('StaffCore', 'mf-staffcore-lite'),
            self::CAPABILITY,
            AdminUrlBuilder::PAGE_SLUG,
            [$this->pageRenderer, 'render'],
            'dashicons-groups',
            30,
        );

        add_submenu_page(
            AdminUrlBuilder::PAGE_SLUG,
            __('Dashboard', 'mf-staffcore-lite'),
            __('Dashboard', 'mf-staffcore-lite'),
            self::CAPABILITY,
            AdminUrlBuilder::PAGE_SLUG,
            [$this->pageRenderer, 'render'],
        );
    }

    /**
     * @param string $classes
     */
    public function adminBodyClass(string $classes): string
    {
        if (! $this->isStaffCoreScreen()) {
            return $classes;
        }

        return $classes . ' mf-staffcore-admin';
    }

    private function isStaffCoreScreen(): bool
    {
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';

        return $page === AdminUrlBuilder::PAGE_SLUG;
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin;

use MF\StaffCore\Authorization\PermissionModuleRegistry;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;

final class AssetRegistrar
{
    private const TABLER_VERSION = '1.4.0';
    private const TABLER_ICONS_VERSION = '3.34.0';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hookSuffix): void
    {
        if (! str_contains($hookSuffix, 'mf-staffcore-lite')) {
            return;
        }

        $baseUrl = MF_STAFFCORE_URL . 'ui/assets/tabler/dist/';
        $version = self::TABLER_VERSION;

        wp_enqueue_style(
            'mf-staffcore-tabler',
            $baseUrl . 'css/tabler.min.css',
            [],
            $version,
        );

        wp_enqueue_style(
            'mf-staffcore-tabler-vendors',
            $baseUrl . 'css/tabler-vendors.min.css',
            ['mf-staffcore-tabler'],
            $version,
        );

        wp_enqueue_style(
            'mf-staffcore-tabler-icons',
            MF_STAFFCORE_URL . 'ui/assets/tabler-icons/dist/tabler-icons.min.css',
            ['mf-staffcore-tabler'],
            self::TABLER_ICONS_VERSION,
        );

        wp_enqueue_script(
            'mf-staffcore-tabler',
            $baseUrl . 'js/tabler.min.js',
            [],
            $version,
            true,
        );

        wp_enqueue_style(
            'mf-staffcore-tom-select',
            MF_STAFFCORE_URL . 'ui/assets/vendor/tom-select/tom-select.bootstrap5.min.css',
            ['mf-staffcore-tabler'],
            '2.3.1',
        );

        wp_enqueue_script(
            'mf-staffcore-tom-select',
            MF_STAFFCORE_URL . 'ui/assets/vendor/tom-select/tom-select.complete.min.js',
            [],
            '2.3.1',
            true,
        );

        wp_enqueue_style(
            'mf-staffcore-admin',
            MF_STAFFCORE_URL . 'ui/assets/css/admin.css',
            ['mf-staffcore-tabler-vendors'],
            MF_STAFFCORE_VERSION,
        );

        wp_enqueue_script(
            'mf-staffcore-theme',
            MF_STAFFCORE_URL . 'ui/assets/js/theme.js',
            [],
            MF_STAFFCORE_VERSION,
            true,
        );

        $this->registerThemeInitScript();

        wp_enqueue_script(
            'mf-staffcore-ui',
            MF_STAFFCORE_URL . 'ui/assets/js/staffcore-ui.js',
            ['mf-staffcore-tabler', 'mf-staffcore-tom-select'],
            MF_STAFFCORE_VERSION,
            true,
        );

        $this->localizeGrantedPermissions();

        $this->enqueuePageScripts();
    }

    /**
     * Early theme apply for #staffcore-app (printed from admin-shell via wp_print_scripts).
     * Uses wp_register_script + wp_add_inline_script — no raw echo of script tags.
     */
    private function registerThemeInitScript(): void
    {
        $js = <<<'JS'
(function () {
    var KEY = 'mf-staffcore-theme';
    var el = document.getElementById('staffcore-app');
    if (!el) return;
    var theme = 'dark';
        try {
        var stored = localStorage.getItem(KEY);
        if (stored === 'light' || stored === 'dark') {
            theme = stored;
        }
    } catch (e) {}
    el.setAttribute('data-bs-theme', theme);
})();
JS;

        // Footer group so admin head does not print before #staffcore-app exists.
        // admin-shell.php calls wp_print_scripts() immediately after the app root opens.
        wp_register_script(
            'mf-staffcore-theme-init',
            false,
            [],
            MF_STAFFCORE_VERSION,
            true,
        );
        wp_enqueue_script('mf-staffcore-theme-init');
        wp_add_inline_script('mf-staffcore-theme-init', $js);
    }

    private function localizeGrantedPermissions(): void
    {
        $container = ServiceContainer::instance();
        $container->authorizationBridge()->resolveCurrentStaffUser();
        $auth = $container->authorization();

        $granted = [];

        foreach (PermissionModuleRegistry::allPermissionKeys() as $key) {
            if ($auth->can($key)) {
                $granted[] = $key;
            }
        }

        wp_localize_script('mf-staffcore-ui', 'mfStaffCorePermissions', [
            'granted' => $granted,
        ]);
    }

    private function enqueuePageScripts(): void
    {
        $menuId = isset($_GET['sc_menu']) ? sanitize_key((string) $_GET['sc_menu']) : '';
        $tabSlug = isset($_GET['sc_tab']) ? sanitize_key((string) $_GET['sc_tab']) : '';
        $view = isset($_GET['sc_view']) ? sanitize_key((string) $_GET['sc_view']) : '';

        wp_enqueue_script(
            'mf-staffcore-notifications',
            MF_STAFFCORE_URL . 'ui/assets/js/modules/notifications.js',
            ['mf-staffcore-ui'],
            MF_STAFFCORE_VERSION,
            true,
        );

        if ($menuId === 'assets' && $tabSlug === 'categories') {
            wp_enqueue_script(
                'mf-staffcore-asset-categories',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/asset-categories.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'assets' && $tabSlug === 'suppliers') {
            wp_enqueue_script(
                'mf-staffcore-suppliers',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/suppliers.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'assets' && $tabSlug === 'assets') {
            wp_enqueue_script(
                'mf-staffcore-assets',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/assets.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'assets' && $tabSlug === 'maintenance') {
            wp_enqueue_script(
                'mf-staffcore-maintenance',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/maintenance.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && $tabSlug === 'positions') {
            wp_enqueue_script(
                'mf-staffcore-positions',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/positions.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && $tabSlug === 'departments') {
            wp_enqueue_script(
                'mf-staffcore-departments',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/departments.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && $tabSlug === 'employees') {
            wp_enqueue_script(
                'mf-staffcore-employees',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/employees.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && $tabSlug === 'attendance') {
            wp_enqueue_script(
                'mf-staffcore-attendance',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/attendance.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && $tabSlug === 'leave-requests') {
            wp_enqueue_script(
                'mf-staffcore-leave-requests',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/leave-requests.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && $tabSlug === 'my-profile') {
            wp_enqueue_script(
                'mf-staffcore-my-profile',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/my-profile.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'hr-management' && in_array($tabSlug, ['payroll', 'my-payslips'], true)) {
            if ($tabSlug === 'payroll') {
                wp_enqueue_script(
                    'mf-staffcore-payroll',
                    MF_STAFFCORE_URL . 'ui/assets/js/modules/payroll.js',
                    ['mf-staffcore-ui'],
                    MF_STAFFCORE_VERSION,
                    true,
                );
            }

            if ($view === 'payslip') {
                wp_enqueue_style(
                    'mf-staffcore-payslip',
                    MF_STAFFCORE_URL . 'ui/assets/css/payslip.css',
                    [],
                    MF_STAFFCORE_VERSION,
                );

                wp_enqueue_script(
                    'mf-staffcore-printthis',
                    MF_STAFFCORE_URL . 'ui/assets/vendor/print-this/printThis.min.js',
                    ['jquery'],
                    '1.15.0',
                    true,
                );

                wp_enqueue_script(
                    'mf-staffcore-payroll-payslip',
                    MF_STAFFCORE_URL . 'ui/assets/js/modules/payroll-payslip.js',
                    ['mf-staffcore-printthis'],
                    MF_STAFFCORE_VERSION,
                    true,
                );
            }
        }

        if ($menuId === 'awards' && $tabSlug === 'company-awards') {
            wp_enqueue_script(
                'mf-staffcore-company-awards',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/company-awards.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'awards' && $tabSlug === 'employee-awards') {
            wp_enqueue_script(
                'mf-staffcore-employee-awards',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/employee-awards.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'performance' && $tabSlug === 'kpi-categories') {
            wp_enqueue_script(
                'mf-staffcore-kpi-categories',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/kpi-categories.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'performance' && $tabSlug === 'kpi-metrics') {
            wp_enqueue_script(
                'mf-staffcore-kpi-metrics',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/kpi-metrics.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'performance' && $tabSlug === 'performance-reviews') {
            wp_enqueue_script(
                'mf-staffcore-performance-reviews',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/performance-reviews.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'performance' && $tabSlug === 'employee-kpis') {
            wp_enqueue_script(
                'mf-staffcore-kpi-employees',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/kpi-employees.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'documents') {
            wp_enqueue_script(
                'mf-staffcore-documents',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/documents.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'settings' && $tabSlug === 'company-information') {
            wp_enqueue_script(
                'mf-staffcore-company-information',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/company-information.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'settings' && $tabSlug === 'users') {
            wp_enqueue_script(
                'mf-staffcore-users',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/users.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'settings' && $tabSlug === 'permissions') {
            wp_enqueue_script(
                'mf-staffcore-permissions',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/permissions.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'settings' && $tabSlug === 'backup-restore') {
            wp_enqueue_script(
                'mf-staffcore-backup-restore',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/backup-restore.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }

        if ($menuId === 'settings' && $tabSlug === 'biometric') {
            wp_enqueue_script(
                'mf-staffcore-biometric',
                MF_STAFFCORE_URL . 'ui/assets/js/modules/biometric.js',
                ['mf-staffcore-ui'],
                MF_STAFFCORE_VERSION,
                true,
            );
        }
    }
}

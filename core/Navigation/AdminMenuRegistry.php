<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

/**
 * Canonical Lite admin sidebar and tab definitions.
 *
 * @see docs/navigation.md
 */
final class AdminMenuRegistry
{
    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     icon: string,
     *     description: string,
     *     route: string,
     *     permission: string,
     *     default_tab: string|null,
     *     tabs: list<array{
     *         id: string,
     *         label: string,
     *         slug: string,
     *         route: string,
     *         permission: string,
     *         icon: string,
     *         description: string
     *     }>
     * }>
     */
    public static function menus(): array
    {
        return [
            self::menu(
                id: 'dashboard',
                label: 'Dashboard',
                icon: 'home',
                description: 'Overview of key HR metrics, shortcuts, and recent activity.',
                route: '/staffcore/admin/dashboard',
                permission: 'dashboard.view',
            ),
            self::menu(
                id: 'notifications',
                label: 'Notifications',
                icon: 'bell',
                description: 'View your in-app StaffCore notifications.',
                route: '/staffcore/admin/notifications',
                permission: 'notifications.view',
            ),
            self::menuWithTabs(
                id: 'team-performance',
                label: 'Team Performance',
                icon: 'users-group',
                description: 'Manager workspace for department team overview and members.',
                route: '/staffcore/admin/team-performance',
                permission: 'my_team.view',
                defaultTab: 'my-team',
                tabs: [
                    ['id' => 'my-team', 'label' => 'My Team', 'slug' => 'my-team', 'icon' => 'users-group', 'description' => 'View your managed departments and team members.', 'permission' => 'my_team.view'],
                ],
            ),
            self::menuWithTabs(
                id: 'hr-management',
                label: 'HR Management',
                icon: 'users',
                description: 'Manage employees, attendance, leave, and payroll.',
                route: '/staffcore/admin/hr-management',
                permission: 'hr_management.view',
                defaultTab: 'employees',
                tabs: [
                    ['id' => 'my-profile', 'label' => 'My Profile', 'slug' => 'my-profile', 'icon' => 'user-circle', 'description' => 'Manage your personal information.', 'permission' => 'my_profile.view'],
                    ['id' => 'my-payslips', 'label' => 'My Payslips', 'slug' => 'my-payslips', 'icon' => 'receipt', 'description' => 'View and download your salary slips.', 'permission' => 'my_payslips.view'],
                    ['id' => 'employees', 'label' => 'Employees', 'slug' => 'employees', 'icon' => 'users', 'description' => 'Manage employee records and profiles.', 'permission' => 'employees.view'],
                    ['id' => 'departments', 'label' => 'Departments', 'slug' => 'departments', 'icon' => 'building', 'description' => 'Organize company departments and structure.', 'permission' => 'departments.view'],
                    ['id' => 'positions', 'label' => 'Positions', 'slug' => 'positions', 'icon' => 'briefcase', 'description' => 'Define job positions and roles.', 'permission' => 'positions.view'],
                    ['id' => 'attendance', 'label' => 'Attendance', 'slug' => 'attendance', 'icon' => 'clock', 'description' => 'View your attendance records and summary.', 'permission' => 'attendance.view'],
                    ['id' => 'leave-requests', 'label' => 'Leave Requests', 'slug' => 'leave-requests', 'icon' => 'calendar-off', 'description' => 'Review and approve employee leave requests.', 'permission' => 'leave_requests.view'],
                    ['id' => 'payroll', 'label' => 'Payroll', 'slug' => 'payroll', 'icon' => 'currency-dollar', 'description' => 'Process salaries and payroll records.', 'permission' => 'payroll.view'],
                ],
            ),
            self::menuWithTabs(
                id: 'assets',
                label: 'Assets',
                icon: 'package',
                description: 'Track company assets, maintenance, and suppliers.',
                route: '/staffcore/admin/assets',
                permission: 'assets.view',
                defaultTab: 'assets',
                tabs: [
                    ['id' => 'assets', 'label' => 'Assets', 'slug' => 'assets', 'icon' => 'package', 'description' => 'Manage physical and digital assets.', 'permission' => 'assets.view'],
                    ['id' => 'categories', 'label' => 'Categories', 'slug' => 'categories', 'icon' => 'category', 'description' => 'Group assets by category.', 'permission' => 'asset_categories.view'],
                    ['id' => 'maintenance', 'label' => 'Maintenance', 'slug' => 'maintenance', 'icon' => 'tools', 'description' => 'Schedule and log asset maintenance.', 'permission' => 'maintenance.view'],
                    ['id' => 'suppliers', 'label' => 'Suppliers', 'slug' => 'suppliers', 'icon' => 'truck-delivery', 'description' => 'Manage asset suppliers and vendors.', 'permission' => 'suppliers.view'],
                ],
            ),
            self::menuWithTabs(
                id: 'settings',
                label: 'Settings',
                icon: 'settings',
                description: 'Configure system, users, and company settings.',
                route: '/staffcore/admin/settings',
                permission: 'settings.view',
                defaultTab: 'users',
                tabs: [
                    ['id' => 'users', 'label' => 'Users Management', 'slug' => 'users', 'icon' => 'users', 'description' => 'Manage admin users and access.', 'permission' => 'users.view'],
                    ['id' => 'permissions', 'label' => 'Permissions', 'slug' => 'permissions', 'icon' => 'shield-lock', 'description' => 'Configure roles and permissions.', 'permission' => 'permissions.view'],
                    ['id' => 'company-information', 'label' => 'Company Information', 'slug' => 'company-information', 'icon' => 'building-skyscraper', 'description' => 'Update company profile and details.', 'permission' => 'company_information.view'],
                    ['id' => 'frontend-panel', 'label' => 'Frontend Panel', 'slug' => 'frontend-panel', 'icon' => 'world-www', 'description' => 'Share the public staff portal URL and WordPress menu link.', 'permission' => 'frontend_panel.view'],
                ],
            ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findMenu(string $menuId): ?array
    {
        foreach (self::menus() as $menu) {
            if ($menu['id'] === $menuId) {
                return $menu;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findTab(string $menuId, string $tabId): ?array
    {
        $menu = self::findMenu($menuId);
        if ($menu === null) {
            return null;
        }

        foreach ($menu['tabs'] as $tab) {
            if ($tab['id'] === $tabId) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findTabBySlug(string $menuId, string $tabSlug): ?array
    {
        $menu = self::findMenu($menuId);
        if ($menu === null) {
            return null;
        }

        foreach ($menu['tabs'] as $tab) {
            if (($tab['slug'] ?? '') === $tabSlug) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * @param list<array{id: string, label: string, slug: string, icon: string, description: string, permission: string}> $tabs
     * @return array<string, mixed>
     */
    private static function menuWithTabs(
        string $id,
        string $label,
        string $icon,
        string $description,
        string $route,
        string $permission,
        string $defaultTab,
        array $tabs,
    ): array {
        $builtTabs = [];

        foreach ($tabs as $tab) {
            $builtTabs[] = [
                'id' => $id . '.' . $tab['id'],
                'label' => $tab['label'],
                'slug' => $tab['slug'],
                'icon' => $tab['icon'],
                'description' => $tab['description'],
                'route' => $route . '/' . $tab['slug'],
                'permission' => $tab['permission'],
            ];
        }

        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'description' => $description,
            'route' => $route,
            'permission' => $permission,
            'default_tab' => $defaultTab,
            'tabs' => $builtTabs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function menu(
        string $id,
        string $label,
        string $icon,
        string $description,
        string $route,
        string $permission,
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'description' => $description,
            'route' => $route,
            'permission' => $permission,
            'default_tab' => null,
            'tabs' => [],
        ];
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

/**
 * Expands HR / Performance tabs into top-level sidebar links for the employee role.
 *
 * @see docs/navigation.md — Employee sidebar
 */
final class EmployeeSidebarMenuRegistry
{
    /**
     * HR tabs shown as flat sidebar items (order preserved).
     *
     * @var list<array{slug: string, label: string, icon: string}>
     */
    private const HR_FLAT_TABS = [
        ['slug' => 'my-profile', 'label' => 'My Profile', 'icon' => 'user-circle'],
        ['slug' => 'leave-requests', 'label' => 'My Leaves', 'icon' => 'calendar-check'],
        ['slug' => 'attendance', 'label' => 'My Attendance', 'icon' => 'clock'],
        ['slug' => 'my-payslips', 'label' => 'My Payslips', 'icon' => 'receipt'],
    ];

    /**
     * @var list<array{slug: string, label: string, icon: string, description?: string}>
     */
    private const PERFORMANCE_FLAT_TABS = [
        ['slug' => 'employee-kpis', 'label' => 'My KPIs', 'icon' => 'chart-dots', 'description' => 'View your performance metrics and reviews.'],
        ['slug' => 'performance-reviews', 'label' => 'My Reviews', 'icon' => 'clipboard-check'],
    ];

    /**
     * @var list<array{slug: string, label: string, icon: string}>
     */
    private const AWARDS_FLAT_TABS = [
        ['slug' => 'employee-awards', 'label' => 'My Awards', 'icon' => 'medal'],
    ];

    /**
     * @param list<array<string, mixed>> $menus
     * @param callable(string): bool $can
     * @return list<array<string, mixed>>
     */
    public static function apply(array $menus, callable $can, bool $isEmployee): array
    {
        if (! $isEmployee) {
            return $menus;
        }

        $expanded = [];

        foreach ($menus as $menu) {
            $menuId = (string) ($menu['id'] ?? '');

            if ($menuId === 'hr-management') {
                $expanded = [...$expanded, ...self::flatItemsFromParent($menu, $menuId, self::HR_FLAT_TABS, $can)];

                continue;
            }

            if ($menuId === 'performance') {
                $expanded = [...$expanded, ...self::flatItemsFromParent($menu, $menuId, self::PERFORMANCE_FLAT_TABS, $can)];

                continue;
            }

            if ($menuId === 'awards') {
                $expanded = [...$expanded, ...self::flatItemsFromParent($menu, $menuId, self::AWARDS_FLAT_TABS, $can)];

                continue;
            }

            $expanded[] = $menu;
        }

        return $expanded;
    }

    public static function shouldHideHorizontalTabs(string $menuId, bool $isEmployee): bool
    {
        if (! $isEmployee) {
            return false;
        }

        return in_array($menuId, ['hr-management', 'performance', 'awards'], true);
    }

    public static function pageTitleForEmployee(
        string $menuId,
        ?string $tabSlug,
        string $defaultTitle,
        bool $isEmployee,
    ): string {
        if (! $isEmployee || $tabSlug === null || $tabSlug === '') {
            return $defaultTitle;
        }

        $translated = self::translateFlatLabel($tabSlug);

        return $translated ?? $defaultTitle;
    }

    public static function pageDescriptionForEmployee(
        string $menuId,
        ?string $tabSlug,
        string $defaultDescription,
        bool $isEmployee,
    ): string {
        if (! $isEmployee || $tabSlug === null || $tabSlug === '') {
            return $defaultDescription;
        }

        $translated = self::translateFlatDescription($tabSlug);

        return $translated ?? $defaultDescription;
    }

    private static function translateFlatLabel(string $slug): ?string
    {
        return match ($slug) {
            'my-profile' => __('My Profile', 'mf-staffcore-lite'),
            'leave-requests' => __('My Leaves', 'mf-staffcore-lite'),
            'attendance' => __('My Attendance', 'mf-staffcore-lite'),
            'my-payslips' => __('My Payslips', 'mf-staffcore-lite'),
            'employee-kpis' => __('My KPIs', 'mf-staffcore-lite'),
            'performance-reviews' => __('My Reviews', 'mf-staffcore-lite'),
            'employee-awards' => __('My Awards', 'mf-staffcore-lite'),
            default => null,
        };
    }

    private static function translateFlatDescription(string $slug): ?string
    {
        return match ($slug) {
            'employee-kpis' => __('View your performance metrics and reviews.', 'mf-staffcore-lite'),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $parentMenu
     * @param list<array{slug: string, label: string, icon: string, description?: string}> $specs
     * @param callable(string): bool $can
     * @return list<array<string, mixed>>
     */
    private static function flatItemsFromParent(
        array $parentMenu,
        string $parentMenuId,
        array $specs,
        callable $can,
    ): array {
        /** @var array<string, array<string, mixed>> $tabsBySlug */
        $tabsBySlug = [];

        foreach ($parentMenu['tabs'] ?? [] as $tab) {
            if (! is_array($tab)) {
                continue;
            }

            $slug = (string) ($tab['slug'] ?? '');

            if ($slug !== '') {
                $tabsBySlug[$slug] = $tab;
            }
        }

        $items = [];

        foreach ($specs as $spec) {
            $slug = $spec['slug'];
            $tab = $tabsBySlug[$slug] ?? null;

            if ($tab === null) {
                continue;
            }

            $permission = (string) ($tab['permission'] ?? '');

            if ($permission === '' || ! $can($permission)) {
                continue;
            }

            $tabId = (string) ($tab['id'] ?? $parentMenuId . '.' . $slug);
            $label = self::translateFlatLabel($slug) ?? $spec['label'];

            $items[] = [
                'id' => $tabId,
                'label' => $label,
                'icon' => $spec['icon'],
                'description' => (string) ($tab['description'] ?? ''),
                'route' => (string) ($tab['route'] ?? ''),
                'permission' => $permission,
                'default_tab' => null,
                'tabs' => [],
                'sidebar_target' => [
                    'menu_id' => $parentMenuId,
                    'tab_slug' => $slug,
                ],
            ];
        }

        return $items;
    }
}

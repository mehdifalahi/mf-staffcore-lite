<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

use MF\StaffCore\Plan\PlanRegistry;

/**
 * Filters admin navigation by permission and resolves the first accessible route.
 *
 * @phpstan-type MenuArray array<string, mixed>
 */
final class AuthorizedMenuRegistry
{
    /**
     * @param list<MenuArray> $menus
     * @param callable(string): bool $can
     * @return list<MenuArray>
     */
    public static function filterMenus(array $menus, callable $can): array
    {
        $filtered = [];

        foreach ($menus as $menu) {
            $visible = self::filterMenu($menu, $can);

            if ($visible !== null) {
                $filtered[] = $visible;
            }
        }

        return $filtered;
    }

    /**
     * @param callable(string): bool $can
     */
    public static function firstAccessibleRoute(callable $can): ?AdminRoute
    {
        foreach (AdminMenuRegistry::menus() as $menu) {
            $menuId = (string) ($menu['id'] ?? '');
            $tabs = $menu['tabs'] ?? [];

            if ($tabs !== []) {
                foreach ($tabs as $tab) {
                    $permission = (string) ($tab['permission'] ?? '');

                    if ($permission !== '' && $can($permission)) {
                        $slug = (string) ($tab['slug'] ?? '');

                        if (PlanRegistry::isRouteAllowed($menuId, $slug)) {
                            return new AdminRoute($menuId, $slug);
                        }
                    }
                }

                continue;
            }

            $permission = (string) ($menu['permission'] ?? '');

            if ($permission !== '' && $can($permission)) {
                if (PlanRegistry::isRouteAllowed($menuId, null)) {
                    return new AdminRoute($menuId, null);
                }
            }
        }

        return null;
    }

    /**
     * @param callable(string): bool $can
     */
    public static function firstAccessibleTabSlugInMenu(string $menuId, callable $can): ?string
    {
        $menu = AdminMenuRegistry::findMenu($menuId);

        if ($menu === null) {
            return null;
        }

        foreach ($menu['tabs'] as $tab) {
            $permission = (string) ($tab['permission'] ?? '');

            if ($permission !== '' && $can($permission)) {
                return (string) ($tab['slug'] ?? '');
            }
        }

        return null;
    }

    /**
     * @param list<MenuArray> $menus
     * @return MenuArray|null
     */
    public static function findMenuInList(array $menus, string $menuId): ?array
    {
        foreach ($menus as $menu) {
            if (($menu['id'] ?? '') === $menuId) {
                return $menu;
            }
        }

        return null;
    }

    /**
     * Hide HR ops-only tabs for line managers (Payroll, Departments, Positions).
     *
     * @param list<MenuArray> $menus
     * @return list<MenuArray>
     */
    public static function filterLineManagerTabs(array $menus, bool $isLineManager): array
    {
        if (! $isLineManager) {
            return $menus;
        }

        $hiddenTabIds = ['payroll', 'departments', 'positions'];

        foreach ($menus as $index => $menu) {
            if (($menu['id'] ?? '') !== 'hr-management') {
                continue;
            }

            $tabs = $menu['tabs'] ?? [];
            $menu['tabs'] = array_values(array_filter(
                $tabs,
                static fn (array $tab): bool => ! in_array((string) ($tab['id'] ?? ''), $hiddenTabIds, true),
            ));
            $menus[$index] = $menu;
        }

        return $menus;
    }

    /**
     * @param MenuArray $menu
     * @param callable(string): bool $can
     * @return MenuArray|null
     */
    private static function filterMenu(array $menu, callable $can): ?array
    {
        $tabs = $menu['tabs'] ?? [];

        if ($tabs !== []) {
            $allowedTabs = [];

            foreach ($tabs as $tab) {
                $permission = (string) ($tab['permission'] ?? '');

                if ($permission !== '' && $can($permission)) {
                    $allowedTabs[] = $tab;
                }
            }

            if ($allowedTabs === []) {
                return null;
            }

            $menu['tabs'] = $allowedTabs;
            $menu['default_tab'] = (string) ($allowedTabs[0]['slug'] ?? $menu['default_tab'] ?? '');

            return $menu;
        }

        $permission = (string) ($menu['permission'] ?? '');

        if ($permission !== '' && $can($permission)) {
            return $menu;
        }

        return null;
    }
}

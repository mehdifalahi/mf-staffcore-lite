<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Navigation\AdminMenuRegistry;

/**
 * Human-readable labels for permission keys ({@see RoleRegistry::allPermissions()}).
 */
final class PermissionLabelRegistry
{
    /**
     * @param list<string> $permissionKeys
     * @return array<string, string> permission key => label
     */
    public static function labelsFor(array $permissionKeys): array
    {
        $fromNav = self::labelsFromNavigation();
        $labels = [];

        foreach ($permissionKeys as $key) {
            $labels[$key] = $fromNav[$key] ?? self::humanizeKey($key);
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    private static function labelsFromNavigation(): array
    {
        $labels = [];

        foreach (AdminMenuRegistry::menus() as $menu) {
            $menuPermission = (string) ($menu['permission'] ?? '');

            if ($menuPermission !== '') {
                $labels[$menuPermission] = (string) ($menu['label'] ?? $menuPermission);
            }

            foreach ($menu['tabs'] as $tab) {
                $tabPermission = (string) ($tab['permission'] ?? '');

                if ($tabPermission !== '') {
                    $labels[$tabPermission] = (string) ($tab['label'] ?? $tabPermission);
                }
            }
        }

        return $labels;
    }

    private static function humanizeKey(string $permission): string
    {
        $module = explode('.', $permission)[0] ?? $permission;
        $module = str_replace('_', ' ', $module);

        return ucwords($module);
    }
}

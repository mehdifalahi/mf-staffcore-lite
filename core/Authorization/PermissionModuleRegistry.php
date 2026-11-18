<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Navigation\AdminMenuRegistry;

/**
 * Permission modules (screens) derived from admin navigation.
 *
 * @see docs/navigation.md
 */
final class PermissionModuleRegistry
{
    /**
     * @return list<array{
     *     module: string,
     *     label: string,
     *     group: string,
     *     icon: string
     * }>
     */
    public static function modules(): array
    {
        $modules = [];

        foreach (AdminMenuRegistry::menus() as $menu) {
            $group = (string) ($menu['label'] ?? '');
            $tabs = $menu['tabs'] ?? [];

            if ($tabs === []) {
                $modules[] = self::moduleFromViewPermission(
                    (string) ($menu['permission'] ?? ''),
                    (string) ($menu['label'] ?? ''),
                    $group,
                    (string) ($menu['icon'] ?? 'folder'),
                );

                continue;
            }

            foreach ($tabs as $tab) {
                $modules[] = self::moduleFromViewPermission(
                    (string) ($tab['permission'] ?? ''),
                    (string) ($tab['label'] ?? ''),
                    $group,
                    (string) ($tab['icon'] ?? 'folder'),
                );
            }
        }

        return self::dedupeModules($modules);
    }

    /**
     * @return list<string> e.g. employees.view, employees.create, …
     */
    public static function allPermissionKeys(): array
    {
        $keys = [];

        foreach (self::modules() as $module) {
            foreach (PermissionAction::ALL as $action) {
                $keys[] = PermissionAction::key((string) $module['module'], $action);
            }
        }

        return $keys;
    }

    public static function findModule(string $moduleId): ?array
    {
        foreach (self::modules() as $module) {
            if ($module['module'] === $moduleId) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @param list<string> $viewPermissions
     * @return list<string> module ids
     */
    public static function moduleIdsFromViewPermissions(array $viewPermissions): array
    {
        $ids = [];

        foreach ($viewPermissions as $permission) {
            if (! str_ends_with($permission, '.' . PermissionAction::VIEW)) {
                continue;
            }

            $ids[] = substr($permission, 0, -strlen('.' . PermissionAction::VIEW));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<array{module: string, label: string, group: string, icon: string}> $modules
     * @return list<array{module: string, label: string, group: string, icon: string}>
     */
    private static function dedupeModules(array $modules): array
    {
        $seen = [];
        $result = [];

        foreach ($modules as $module) {
            $id = (string) $module['module'];

            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $result[] = $module;
        }

        return $result;
    }

    /**
     * @return array{module: string, label: string, group: string, icon: string}
     */
    private static function moduleFromViewPermission(
        string $viewPermission,
        string $label,
        string $group,
        string $icon,
    ): array {
        $module = self::moduleIdFromViewPermission($viewPermission);

        return [
            'module' => $module,
            'label' => $label,
            'group' => $group,
            'icon' => $icon,
        ];
    }

    private static function moduleIdFromViewPermission(string $viewPermission): string
    {
        if ($viewPermission === '') {
            return '';
        }

        if (! str_ends_with($viewPermission, '.' . PermissionAction::VIEW)) {
            return '';
        }

        return substr($viewPermission, 0, -strlen('.' . PermissionAction::VIEW));
    }
}

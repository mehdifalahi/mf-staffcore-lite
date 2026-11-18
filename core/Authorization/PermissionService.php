<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Authorization\Repositories\RolePermissionRepository;

final class PermissionService
{
    /** @var array<string, list<string>> */
    private array $cache = [];

    public function __construct(
        private readonly RolePermissionRepository $permissions,
    ) {
    }

    public function roleHasPermission(string $roleId, string $permission): bool
    {
        if (! RoleRegistry::isValidRole($roleId)) {
            return false;
        }

        return in_array($permission, $this->permissionsForRole($roleId), true);
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $roleId): array
    {
        if (! RoleRegistry::isValidRole($roleId)) {
            return [];
        }

        if (isset($this->cache[$roleId])) {
            return $this->cache[$roleId];
        }

        if ($this->permissions->countForRole($roleId) === 0) {
            $this->seedDefaultsForRole($roleId);
        }

        $this->cache[$roleId] = $this->permissions->permissionsForRole($roleId);

        return $this->cache[$roleId];
    }

    /**
     * @param list<string> $permissionKeys
     */
    public function replaceRolePermissions(string $roleId, array $permissionKeys): void
    {
        if (! RoleRegistry::isValidRole($roleId)) {
            throw new AuthorizationException(esc_html('Invalid role.'));
        }

        $allowed = array_flip(PermissionModuleRegistry::allPermissionKeys());
        $filtered = [];

        foreach ($permissionKeys as $key) {
            if (isset($allowed[$key])) {
                $filtered[] = $key;
            }
        }

        $filtered = array_values(array_unique($filtered));

        $this->permissions->replaceForRole($roleId, $filtered);
        unset($this->cache[$roleId]);
    }

    public function ensureAllRoleDefaultsSeeded(): void
    {
        foreach (RoleRegistry::roleIds() as $roleId) {
            if ($this->permissions->countForRole($roleId) === 0) {
                $this->seedDefaultsForRole($roleId);
            }
        }
    }

    /**
     * Merge default permission keys for modules that have no grants yet (module upgrades).
     *
     * Used on Pro activation after Lite→Pro so Documents/Awards/KPI/Biometric/Backup
     * defaults appear without wiping admin customizations on already-granted modules.
     *
     * Do not call on every request — that would undo intentional empty modules.
     */
    public function ensureMissingDefaultPermissions(): void
    {
        foreach (RoleRegistry::roleIds() as $roleId) {
            $defaults = RoleRegistry::defaultPermissionsForRole($roleId);
            $current = $this->permissionsForRole($roleId);
            $modulesWithGrants = $this->moduleIdsWithAnyGrant($current);
            $missing = [];

            foreach ($defaults as $permission) {
                $moduleId = $this->moduleIdFromPermissionKey($permission);

                if ($moduleId === '' || in_array($moduleId, $modulesWithGrants, true)) {
                    continue;
                }

                $missing[] = $permission;
            }

            if ($missing === []) {
                continue;
            }

            $this->permissions->replaceForRole($roleId, array_values(array_unique([...$current, ...$missing])));
            unset($this->cache[$roleId]);
        }
    }

    /**
     * @param list<string> $permissions
     * @return list<string>
     */
    private function moduleIdsWithAnyGrant(array $permissions): array
    {
        $moduleIds = [];

        foreach ($permissions as $permission) {
            $moduleId = $this->moduleIdFromPermissionKey($permission);

            if ($moduleId !== '') {
                $moduleIds[] = $moduleId;
            }
        }

        return array_values(array_unique($moduleIds));
    }

    private function moduleIdFromPermissionKey(string $permission): string
    {
        $dot = strrpos($permission, '.');

        if ($dot === false) {
            return '';
        }

        $action = substr($permission, $dot + 1);

        if (! PermissionAction::isValid($action)) {
            return '';
        }

        return substr($permission, 0, $dot);
    }

    private function seedDefaultsForRole(string $roleId): void
    {
        $defaults = RoleRegistry::defaultPermissionsForRole($roleId);
        $this->permissions->replaceForRole($roleId, $defaults);
        $this->cache[$roleId] = $defaults;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}


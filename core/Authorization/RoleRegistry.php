<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

/**
 * Canonical fixed StaffCore roles and default permission sets (seeded to DB).
 *
 * @see docs/roles.md
 */
final class RoleRegistry
{
    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_HR_MANAGER = 'hr_manager';

    public const ROLE_EMPLOYEE = 'employee';

    /**
     * @return list<array{id: string, label: string, description: string}>
     */
    public static function roles(): array
    {
        return [
            [
                'id' => self::ROLE_ADMINISTRATOR,
                'label' => 'Administrator',
                'description' => 'Full access to all StaffCore modules and settings.',
            ],
            [
                'id' => self::ROLE_HR_MANAGER,
                'label' => 'HR Manager',
                'description' => 'Manage HR operations. User and permission administration configurable separately.',
            ],
            [
                'id' => self::ROLE_EMPLOYEE,
                'label' => 'Employee',
                'description' => 'Self-service defaults (Dashboard, Notifications, HR tabs, My KPIs). SELF scope applies; admin may adjust in Settings → Permissions.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function roleIds(): array
    {
        return array_map(
            static fn (array $role): string => (string) $role['id'],
            self::roles(),
        );
    }

    /**
     * @return array{id: string, label: string, description: string}|null
     */
    public static function findRole(string $roleId): ?array
    {
        foreach (self::roles() as $role) {
            if ($role['id'] === $roleId) {
                return $role;
            }
        }

        return null;
    }

    public static function isValidRole(string $roleId): bool
    {
        return self::findRole($roleId) !== null;
    }

    public static function displayLabel(string $roleId): string
    {
        return match ($roleId) {
            self::ROLE_ADMINISTRATOR => 'Admin',
            self::ROLE_HR_MANAGER => 'Manager',
            self::ROLE_EMPLOYEE => 'Employee',
            default => $roleId,
        };
    }

    /**
     * Default permission keys for initial DB seed ({module}.{view|create|edit|delete}).
     *
     * @return list<string>
     */
    public static function defaultPermissionsForRole(string $roleId): array
    {
        return match ($roleId) {
            self::ROLE_ADMINISTRATOR => array_values(array_filter(
                PermissionModuleRegistry::allPermissionKeys(),
                static fn (string $key): bool => ! str_starts_with($key, 'my_team.')
                    && ! str_starts_with($key, 'my_profile.')
                    && ! str_starts_with($key, 'my_payslips.'),
            )),
            self::ROLE_HR_MANAGER => self::defaultManagerPermissions(),
            self::ROLE_EMPLOYEE => self::defaultEmployeePermissions(),
            default => [],
        };
    }

    /**
     * Employee default grants — commercial self-service bundle (10 keys).
     *
     * Admin may adjust via Settings → Permissions. SELF scope still applies.
     *
     * @see .cursor/rules/mf-staffcore-employee-access.mdc §10
     * @return list<string>
     */
    public static function defaultEmployeePermissions(): array
    {
        return array_values(array_unique([
            ...self::expandModuleActions(
                ['dashboard', 'notifications', 'my_profile', 'my_payslips', 'kpi_employees'],
                [PermissionAction::VIEW],
            ),
            ...self::expandModuleActions(
                ['attendance'],
                [PermissionAction::VIEW, PermissionAction::CREATE],
            ),
            ...self::expandModuleActions(
                ['leave_requests'],
                [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::EDIT],
            ),
        ]));
    }

    /**
     * Manager default grants — operational HR (not Settings admin).
     *
     * Line manager vs HR ops: same seed; payroll/departments/positions tabs hidden at runtime
     * for line managers. Company awards are view-only.
     *
     * @see .cursor/rules/mf-staffcore-manager-access.mdc §5
     * @return list<string>
     */
    public static function defaultManagerPermissions(): array
    {
        $settingsAdminModules = [
            'users',
            'permissions',
            'backup_restore',
            'company_information',
            'frontend_panel',
            'biometric_devices',
        ];

        $employeeOnlyModules = [
            'my_profile',
            'my_payslips',
        ];

        $managerExcludedModules = [
            'kpi_categories',
            'kpi_metrics',
        ]; // KPI catalog is admin-only by default; HR ops may be granted in Settings → Permissions

        $viewOnlyModules = [
            'dashboard',
            'notifications',
            'my_team',
            'company_awards',
        ];

        $allModuleIds = array_map(
            static fn (array $module): string => (string) $module['module'],
            PermissionModuleRegistry::modules(),
        );

        $fullCrudModules = array_values(array_diff(
            $allModuleIds,
            $settingsAdminModules,
            $viewOnlyModules,
            $employeeOnlyModules,
            $managerExcludedModules,
        ));

        $keys = [];

        foreach ($viewOnlyModules as $moduleId) {
            if (PermissionModuleRegistry::findModule($moduleId) === null) {
                continue;
            }

            $keys[] = PermissionAction::key($moduleId, PermissionAction::VIEW);
        }

        return array_values(array_unique([
            ...$keys,
            ...self::expandModuleActions($fullCrudModules),
            ...self::expandModuleActions(
                ['biometric_devices'],
                [PermissionAction::VIEW, PermissionAction::EDIT],
            ),
        ]));
    }

    /**
     * @param list<string> $moduleIds
     * @param list<string> $actions
     * @return list<string>
     */
    private static function expandModuleActions(
        array $moduleIds,
        array $actions = PermissionAction::ALL,
    ): array {
        $keys = [];

        foreach ($moduleIds as $moduleId) {
            foreach ($actions as $action) {
                $keys[] = PermissionAction::key($moduleId, $action);
            }
        }

        return array_values(array_unique($keys));
    }
}

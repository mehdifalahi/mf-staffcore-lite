<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\PermissionAction;
use MF\StaffCore\Authorization\PermissionModuleRegistry;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Platforms\WordPress\Ajax\PermissionsAjaxHandler;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;

final class PermissionsPresenter
{
    public function render(): void
    {
        $container = ServiceContainer::instance();
        $permissionService = $container->permissionService();
        $permissionService->ensureAllRoleDefaultsSeeded();

        $roles = RoleRegistry::roles();
        $modules = PermissionModuleRegistry::modules();
        $modulesByGroup = self::groupModules($modules);

        $matrix = [];

        foreach (RoleRegistry::roleIds() as $roleId) {
            $granted = $permissionService->permissionsForRole($roleId);
            $matrix[$roleId] = array_fill_keys($granted, true);
        }

        $canEdit = $container->authorization()->can(
            PermissionAction::key('permissions', PermissionAction::EDIT),
        );

        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = PermissionsAjaxHandler::nonce();
        $actionLabels = [
            PermissionAction::VIEW => __('View', 'mf-staffcore-lite'),
            PermissionAction::CREATE => __('Create', 'mf-staffcore-lite'),
            PermissionAction::EDIT => __('Edit', 'mf-staffcore-lite'),
            PermissionAction::DELETE => __('Delete', 'mf-staffcore-lite'),
        ];

        include MF_STAFFCORE_PATH . 'ui/components/permissions-matrix.php';
    }

    /**
     * @param list<array{module: string, label: string, group: string, icon: string}> $modules
     * @return array<string, list<array{module: string, label: string, group: string, icon: string}>>
     */
    private static function groupModules(array $modules): array
    {
        $groups = [];

        foreach ($modules as $module) {
            $group = (string) ($module['group'] ?? '');

            if (! isset($groups[$group])) {
                $groups[$group] = [];
            }

            $groups[$group][] = $module;
        }

        return $groups;
    }
}

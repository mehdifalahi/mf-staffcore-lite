<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Authorization\PermissionAction;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;

/**
 * Shared StaffCore permission + nonce gate for wp_ajax handlers.
 */
trait StaffCoreAjaxAuthorization
{
    private function authorizeStaffCoreRequest(string $permission, string $nonceAction): void
    {
        // Callers (public wp_ajax methods) must also invoke check_ajax_referer()
        // inline so Plugin Check can see it; this remains defense-in-depth.
        check_ajax_referer($nonceAction, 'nonce');

        ServiceContainer::instance()
            ->authorizationBridge()
            ->authorizePermission($permission);
    }

    private function authorizeModuleView(string $module, string $nonceAction): void
    {
        $this->authorizeStaffCoreRequest(
            PermissionAction::key($module, PermissionAction::VIEW),
            $nonceAction,
        );
    }

    private function authorizeModuleCreate(string $module, string $nonceAction): void
    {
        $this->authorizeStaffCoreRequest(
            PermissionAction::key($module, PermissionAction::CREATE),
            $nonceAction,
        );
    }

    private function authorizeModuleEdit(string $module, string $nonceAction): void
    {
        $this->authorizeStaffCoreRequest(
            PermissionAction::key($module, PermissionAction::EDIT),
            $nonceAction,
        );
    }

    private function authorizeModuleDelete(string $module, string $nonceAction): void
    {
        $this->authorizeStaffCoreRequest(
            PermissionAction::key($module, PermissionAction::DELETE),
            $nonceAction,
        );
    }

    private function authorizeModuleSave(string $module, string $nonceAction): void
    {
        // Public save() must call check_ajax_referer() first (scanner-visible).
        // Re-verify here before reading $_POST['id'] for create vs edit permission.
        check_ajax_referer($nonceAction, 'nonce');

        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;

        $permission = $id > 0
            ? PermissionAction::key($module, PermissionAction::EDIT)
            : PermissionAction::key($module, PermissionAction::CREATE);

        ServiceContainer::instance()
            ->authorizationBridge()
            ->authorizePermission($permission);
    }
}

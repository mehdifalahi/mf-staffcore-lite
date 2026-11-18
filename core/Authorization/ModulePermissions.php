<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

/**
 * Resolved CRUD flags for one module and the current user.
 */
final class ModulePermissions
{
    public function __construct(
        public readonly bool $view,
        public readonly bool $create,
        public readonly bool $edit,
        public readonly bool $delete,
    ) {
    }

    public static function forModule(AuthorizationService $authorization, string $module): self
    {
        return new self(
            view: $authorization->can(PermissionAction::key($module, PermissionAction::VIEW)),
            create: $authorization->can(PermissionAction::key($module, PermissionAction::CREATE)),
            edit: $authorization->can(PermissionAction::key($module, PermissionAction::EDIT)),
            delete: $authorization->can(PermissionAction::key($module, PermissionAction::DELETE)),
        );
    }

    /**
     * @return array{view: bool, create: bool, edit: bool, delete: bool}
     */
    public function toArray(): array
    {
        return [
            'view' => $this->view,
            'create' => $this->create,
            'edit' => $this->edit,
            'delete' => $this->delete,
        ];
    }
}

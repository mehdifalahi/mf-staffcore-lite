<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Auth\CurrentPlatformUserProvider;
use MF\StaffCore\Navigation\AdminMenuRegistry;
use MF\StaffCore\Plan\PlanRegistry;

final class AuthorizationService
{
    private ?StaffUserContext $resolvedContext = null;

    private bool $contextResolved = false;

    public function __construct(
        private readonly StaffUserResolverInterface $staffUserResolver,
        private readonly CurrentPlatformUserProvider $platformUsers,
        private readonly PermissionService $permissionService,
    ) {
    }

    /**
     * Resolved once per service instance (request-scoped via container).
     */
    public function current(): ?StaffUserContext
    {
        if (! $this->contextResolved) {
            $this->resolvedContext = $this->resolveCurrent();
            $this->contextResolved = true;
        }

        return $this->resolvedContext;
    }

    /**
     * Allows the platform adapter to set context (e.g. WP manage_options fallback).
     */
    public function setCurrentContext(?StaffUserContext $context): void
    {
        $this->resolvedContext = $context;
        $this->contextResolved = true;
    }

    public function resetCurrent(): void
    {
        $this->resolvedContext = null;
        $this->contextResolved = false;
    }

    public function requireActiveStaffUser(): StaffUserContext
    {
        $context = $this->current();

        if ($context === null || ! $context->isActive()) {
            throw new AuthorizationException(esc_html('An active StaffCore user account is required.'));
        }

        return $context;
    }

    public function can(string $permission): bool
    {
        $context = $this->current();

        if ($context === null || ! $context->isActive()) {
            return false;
        }

        if ($this->isDeniedByRolePolicy($context, $permission)) {
            return false;
        }

        return $this->permissionService->roleHasPermission($context->role, $permission);
    }

    public function modulePermissions(string $module): ModulePermissions
    {
        return ModulePermissions::forModule($this, $module);
    }

    public function require(string $permission): void
    {
        if (! $this->can($permission)) {
            throw new AuthorizationException(
                esc_html(sprintf('Permission denied: %s', $permission)),
            );
        }
    }

    /**
     * @param array<string, mixed> $menu
     */
    public function permissionForMenu(array $menu): string
    {
        return (string) ($menu['permission'] ?? '');
    }

    /**
     * @param array<string, mixed> $tab
     */
    public function permissionForTab(array $tab): string
    {
        return (string) ($tab['permission'] ?? '');
    }

    public function canAccessRoute(string $menuId, ?string $tabSlug): bool
    {
        if (! PlanRegistry::isRouteAllowed($menuId, $tabSlug)) {
            return false;
        }

        $menu = AdminMenuRegistry::findMenu($menuId);

        if ($menu === null) {
            return false;
        }

        $tabs = $menu['tabs'] ?? [];

        if ($tabs !== []) {
            $slug = $tabSlug !== null && $tabSlug !== ''
                ? $tabSlug
                : (string) ($menu['default_tab'] ?? '');

            $tab = AdminMenuRegistry::findTabBySlug($menuId, $slug);

            if ($tab === null) {
                return false;
            }

            return $this->can($this->permissionForTab($tab));
        }

        return $this->can($this->permissionForMenu($menu));
    }

    private function resolveCurrent(): ?StaffUserContext
    {
        $platformUserId = $this->platformUsers->getCurrentPlatformUserId();

        if ($platformUserId <= 0) {
            return null;
        }

        return $this->staffUserResolver->resolveByPlatformUserId($platformUserId);
    }

    /**
     * Hard role gates for sensitive modules.
     *
     * Some screens must remain restricted even if someone manually grants permissions in the DB.
     */
    private function isDeniedByRolePolicy(StaffUserContext $context, string $permission): bool
    {
        if ($context->role === RoleRegistry::ROLE_HR_MANAGER) {
            if (
                str_starts_with($permission, 'kpi_categories.')
                || str_starts_with($permission, 'kpi_metrics.')
            ) {
                return true;
            }
        }

        return false;
    }
}

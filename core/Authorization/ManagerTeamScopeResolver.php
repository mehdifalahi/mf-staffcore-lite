<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Authorization\Repositories\TeamScopeRepository;

/**
 * Resolves team-level employee scope for line managers ({@see RoleRegistry::ROLE_HR_MANAGER}).
 *
 * My Team roster always uses {@see TeamScopeRepository} / department.manager_id only —
 * manager_scope_mode does not change organizational team membership
 * (see mf-staffcore-manager-scope-setting.mdc).
 */
final class ManagerTeamScopeResolver
{
    /** @var list<string> */
    private const TEAM_SCOPED_MODULES = [
        'dashboard',
        'employees',
        'attendance',
        'leave_requests',
        'documents',
        'employee_awards',
        'performance_reviews',
        'kpi_employees',
        'payroll',
    ];

    /** @var list<string> */
    private const TEAM_PLUS_SELF_MODULES = [
        'performance_reviews',
        'kpi_employees',
    ];

    public function __construct(
        private readonly TeamScopeRepository $teamScope,
        private readonly EmployeeScopeResolver $employeeScope,
    ) {
    }

    public function isLineManager(?StaffUserContext $context): bool
    {
        if ($context === null || $context->role !== RoleRegistry::ROLE_HR_MANAGER) {
            return false;
        }

        return match ($this->effectiveScopeMode($context)) {
            ManagerScopeModeRegistry::TEAM => true,
            ManagerScopeModeRegistry::ALL => false,
            default => $this->teamScope->isLineManager($context->platformUserId),
        };
    }

    /**
     * @return list<int>
     */
    public function managedDepartmentIds(int $platformUserId): array
    {
        return $this->teamScope->managedDepartmentIds($platformUserId);
    }

    /**
     * @return list<int>
     */
    public function teamEmployeeIds(int $platformUserId): array
    {
        return $this->teamScope->teamEmployeeIds($platformUserId);
    }

    /**
     * Employee IDs to filter list queries for the given module.
     *
     * @return list<int>|null null = unrestricted (administrator, HR ops, or module not team-scoped)
     *
     * @throws AuthorizationException when team-only scope is set but no department manager link exists
     */
    public function scopeEmployeeIdsForModule(?StaffUserContext $context, string $moduleId): ?array
    {
        if ($context === null || $context->role === RoleRegistry::ROLE_ADMINISTRATOR) {
            return null;
        }

        if ($context->role === RoleRegistry::ROLE_EMPLOYEE) {
            $employeeId = $this->employeeScope->linkedEmployeeId($context);

            return $employeeId !== null ? [$employeeId] : [];
        }

        if ($context->role !== RoleRegistry::ROLE_HR_MANAGER) {
            return null;
        }

        $mode = $this->effectiveScopeMode($context);

        if ($mode === ManagerScopeModeRegistry::ALL) {
            return null;
        }

        if (! in_array($moduleId, self::TEAM_SCOPED_MODULES, true)) {
            return null;
        }

        if ($mode === ManagerScopeModeRegistry::AUTO && ! $this->teamScope->isLineManager($context->platformUserId)) {
            return null;
        }

        if (
            $mode === ManagerScopeModeRegistry::TEAM
            && $this->teamScope->managedDepartmentIds($context->platformUserId) === []
        ) {
            throw new AuthorizationException(esc_html('You have team-only data access but no department manager assignment. '
                . 'Assign this manager to a department or change the data access scope on Settings → Users.'));
        }

        return $this->teamScopeWithOptionalSelf($context->platformUserId, $moduleId);
    }

    /**
     * @return list<int>|null
     */
    public function scopeEmployeeIdsForPerformance(?StaffUserContext $context): ?array
    {
        return $this->scopeEmployeeIdsForModule($context, 'performance_reviews');
    }

    /**
     * @return list<int>
     */
    private function teamScopeWithOptionalSelf(int $platformUserId, string $moduleId): array
    {
        $ids = $this->teamScope->teamEmployeeIds($platformUserId);

        if (! in_array($moduleId, self::TEAM_PLUS_SELF_MODULES, true)) {
            return $ids;
        }

        $selfId = $this->teamScope->managerEmployeeId($platformUserId);

        if ($selfId !== null && ! in_array($selfId, $ids, true)) {
            $ids[] = $selfId;
        }

        return $ids;
    }

    private function effectiveScopeMode(StaffUserContext $context): string
    {
        if ($context->role !== RoleRegistry::ROLE_HR_MANAGER) {
            return ManagerScopeModeRegistry::AUTO;
        }

        $mode = $context->managerScopeMode;

        return ManagerScopeModeRegistry::isValid($mode) ? $mode : ManagerScopeModeRegistry::AUTO;
    }
}


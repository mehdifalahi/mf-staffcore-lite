<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Modules\Employees\Repositories\EmployeeRepository;

/**
 * Resolves row-level employee scope for the {@see RoleRegistry::ROLE_EMPLOYEE} role.
 */
final class EmployeeScopeResolver
{
    public function __construct(
        private readonly EmployeeRepository $employees,
    ) {
    }

    public function linkedEmployeeId(?StaffUserContext $context): ?int
    {
        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            return null;
        }

        return $this->employees->findIdByPlatformUserId($context->platformUserId);
    }

    public function mustHaveLinkedEmployee(?StaffUserContext $context): int
    {
        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            throw new AuthorizationException(esc_html('Employee scope is not applicable.'));
        }

        $employeeId = $this->linkedEmployeeId($context);

        if ($employeeId === null || $employeeId <= 0) {
            throw new AuthorizationException(esc_html('No employee record is linked to your account. Contact HR to complete your profile.'));
        }

        return $employeeId;
    }

    /**
     * @return int|null Employee ID to filter queries, or null when unrestricted (admin / HR).
     */
    public function scopeEmployeeIdForContext(?StaffUserContext $context): ?int
    {
        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            return null;
        }

        return $this->mustHaveLinkedEmployee($context);
    }

    public function enforceEmployeeId(int $employeeId, ?int $scopeEmployeeId): int
    {
        if ($scopeEmployeeId === null) {
            return $employeeId;
        }

        return $scopeEmployeeId;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function enforceEmployeeIdInScope(int $employeeId, ?array $scopeEmployeeIds): int
    {
        if ($scopeEmployeeIds === null) {
            return $employeeId;
        }

        if ($employeeId <= 0 || ! in_array($employeeId, $scopeEmployeeIds, true)) {
            throw new AuthorizationException(esc_html('You are not allowed to access this employee record.'));
        }

        return $employeeId;
    }

    /**
     * Self-service forces a single scoped employee; team scope validates the submitted id.
     *
     * @param list<int>|null $scopeEmployeeIds
     */
    public function normalizeInputEmployeeId(int $employeeId, ?array $scopeEmployeeIds): int
    {
        if ($scopeEmployeeIds === null) {
            return $employeeId;
        }

        if (count($scopeEmployeeIds) === 1) {
            return $scopeEmployeeIds[0];
        }

        return $this->enforceEmployeeIdInScope($employeeId, $scopeEmployeeIds);
    }

    /**
     * True when the UI should lock the employee field to a single linked record.
     *
     * @param list<int>|null $scopeEmployeeIds
     */
    public function isSingleEmployeeScope(?array $scopeEmployeeIds): bool
    {
        return $scopeEmployeeIds !== null && count($scopeEmployeeIds) === 1;
    }
}


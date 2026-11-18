<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization\Repositories;

use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Departments\DTO\Department;

/**
 * Canonical SQL for line-manager team scope (department.manager_id → employee.user_id).
 *
 * @see modules/MyTeam/Repositories/MyTeamRepository
 */
final class TeamScopeRepository
{
    public function __construct(
        private readonly TableGateway $db,
    ) {
    }

    public function isLineManager(int $platformUserId): bool
    {
        return $this->managedDepartmentIds($platformUserId) !== [];
    }

    /**
     * @return list<int>
     */
    public function managedDepartmentIds(int $platformUserId): array
    {
        if ($platformUserId <= 0) {
            return [];
        }

        $departmentTable = $this->db->resolveTableName('department');
        $employeeTable = $this->db->resolveTableName('employee');

        $rows = $this->db->getResults(
            'SELECT d.id
             FROM `' . $departmentTable . '` AS d
             INNER JOIN `' . $employeeTable . '` AS m ON m.id = d.manager_id
             WHERE m.user_id = %d
               AND d.status = %s
             ORDER BY d.id DESC',
            [$platformUserId, Department::STATUS_ACTIVE],
        );

        return array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        ));
    }

    /**
     * Team member employee IDs (excludes the manager's own employee row).
     *
     * @return list<int>
     */
    public function teamEmployeeIds(int $platformUserId): array
    {
        if ($platformUserId <= 0) {
            return [];
        }

        $employeeTable = $this->db->resolveTableName('employee');
        $departmentTable = $this->db->resolveTableName('department');
        [$whereSql, $whereParams] = $this->teamMemberWhereClause($platformUserId);

        $rows = $this->db->getResults(
            'SELECT e.id
             FROM `' . $employeeTable . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             WHERE ' . $whereSql . '
             ORDER BY e.id DESC',
            $whereParams,
        );

        return array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        ));
    }

    public function managerEmployeeId(int $platformUserId): ?int
    {
        if ($platformUserId <= 0) {
            return null;
        }

        $employeeTable = $this->db->resolveTableName('employee');
        $id = $this->db->getVar(
            'SELECT id FROM `' . $employeeTable . '` WHERE user_id = %d LIMIT 1',
            [$platformUserId],
        );

        if ($id === null || (int) $id <= 0) {
            return null;
        }

        return (int) $id;
    }

    /**
     * WHERE clause for team members: employees in departments managed by the platform user,
     * excluding the manager's own employee row.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public function teamMemberWhereClause(int $platformUserId): array
    {
        if ($platformUserId <= 0) {
            return ['0 = 1', []];
        }

        $employeeTable = $this->db->resolveTableName('employee');

        return [
            'd.manager_id IN (
                SELECT m.id
                FROM `' . $employeeTable . '` AS m
                WHERE m.user_id = %d
            ) AND e.id != d.manager_id',
            [$platformUserId],
        ];
    }
}

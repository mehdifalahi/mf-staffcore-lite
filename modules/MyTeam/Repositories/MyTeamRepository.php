<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyTeam\Repositories;

use MF\StaffCore\Authorization\Repositories\TeamScopeRepository;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\MyTeam\DTO\MyTeamDepartment;
use MF\StaffCore\Modules\MyTeam\DTO\MyTeamMember;
use MF\StaffCore\Support\PaginatedList;

/**
 * My Team roster is organizational only (department.manager_id).
 * staff_user.manager_scope_mode does not alter this module — see mf-staffcore-manager-scope-setting.mdc.
 */
final class MyTeamRepository
{
    public function __construct(
        private readonly TableGateway $db,
        private readonly TeamScopeRepository $teamScope,
    ) {
    }

    /**
     * @return list<MyTeamDepartment>
     */
    public function findManagedDepartments(int $platformUserId): array
    {
        if ($platformUserId <= 0) {
            return [];
        }

        $departmentTable = $this->db->resolveTableName('department');
        $employeeTable = $this->db->resolveTableName('employee');

        $rows = $this->db->getResults(
            'SELECT d.id, d.name, d.code
             FROM `' . $departmentTable . '` AS d
             INNER JOIN `' . $employeeTable . '` AS m ON m.id = d.manager_id
             WHERE m.user_id = %d
               AND d.status = %s
             ORDER BY d.id DESC',
            [$platformUserId, 'ACTIVE'],
        );

        return array_map(
            static fn (array $row): MyTeamDepartment => MyTeamDepartment::fromRow($row),
            $rows,
        );
    }

    public function countTeamMembers(int $platformUserId): int
    {
        return $this->countByStatus($platformUserId, null);
    }

    public function countActiveMembers(int $platformUserId): int
    {
        return $this->countByStatus($platformUserId, Employee::STATUS_ACTIVE);
    }

    public function countOnLeaveMembers(int $platformUserId): int
    {
        return $this->countByStatus($platformUserId, Employee::STATUS_ON_LEAVE);
    }

    /**
     * @return PaginatedList<MyTeamMember>
     */
    public function findPage(int $platformUserId, int $page, int $perPage): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        if ($platformUserId <= 0) {
            return new PaginatedList([], 0, $page, $perPage);
        }

        $employeeTable = $this->db->resolveTableName('employee');
        $departmentTable = $this->db->resolveTableName('department');
        $positionTable = $this->db->resolveTableName('position');
        [$teamWhereSql, $teamWhereParams] = $this->teamScope->teamMemberWhereClause($platformUserId);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*)
             FROM `' . $employeeTable . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             WHERE ' . $teamWhereSql,
            $teamWhereParams,
        );

        $rows = $this->db->getResults(
            'SELECT e.id, e.first_name, e.last_name, e.email, e.photo, e.employment_status, e.hire_date,
                    d.name AS department_name,
                    p.title AS position_title
             FROM `' . $employeeTable . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             WHERE ' . $teamWhereSql . '
             ORDER BY e.id DESC
             LIMIT %d OFFSET %d',
            array_merge($teamWhereParams, [$perPage, $offset]),
        );

        $items = array_map(
            static fn (array $row): MyTeamMember => MyTeamMember::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    private function countByStatus(int $platformUserId, ?string $status): int
    {
        if ($platformUserId <= 0) {
            return 0;
        }

        $employeeTable = $this->db->resolveTableName('employee');
        $departmentTable = $this->db->resolveTableName('department');
        [$teamWhereSql, $teamWhereParams] = $this->teamScope->teamMemberWhereClause($platformUserId);

        $where = [$teamWhereSql];
        $params = $teamWhereParams;

        if ($status !== null) {
            $where[] = 'e.employment_status = %s';
            $params[] = $status;
        }

        return (int) $this->db->getVar(
            'SELECT COUNT(*)
             FROM `' . $employeeTable . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             WHERE ' . implode(' AND ', $where),
            $params,
        );
    }
}

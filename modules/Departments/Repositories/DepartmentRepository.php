<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Departments\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Modules\Departments\DTO\DepartmentInput;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\Users\DTO\StaffUser;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class DepartmentRepository
{
    private const TABLE = 'department';

    private const SELECT_COLUMNS = 'd.id, d.name, d.code, d.description, d.manager_id, d.status,
        d.created_at, d.updated_at, d.created_by, d.updated_by';

    private const JOIN_MANAGER = ", TRIM(CONCAT(e.first_name, ' ', e.last_name)) AS manager_name";

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Department>
     */
    public function findPage(int $page, int $perPage): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');

        $total = (int) $this->db->getVar('SELECT COUNT(*) FROM `' . $table . '` AS d');

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_MANAGER . '
             FROM `' . $table . '` AS d
             LEFT JOIN `' . $employeeTable . '` AS e ON e.id = d.manager_id
             ORDER BY d.id DESC
             LIMIT %d OFFSET %d',
            [$perPage, $offset],
        );

        $items = array_map(
            static fn (array $row): Department => Department::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @return list<Department>
     */
    public function listActive(): array
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '` AS d
             WHERE d.status = %s
             ORDER BY d.name ASC',
            [Department::STATUS_ACTIVE],
        );

        return array_map(
            static fn (array $row): Department => Department::fromRow($row),
            $rows,
        );
    }

    public function findById(int $id): ?Department
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_MANAGER . '
             FROM `' . $table . '` AS d
             LEFT JOIN `' . $employeeTable . '` AS e ON e.id = d.manager_id
             WHERE d.id = %d
             LIMIT 1',
            [$id],
        );

        return $row !== null ? Department::fromRow($row) : null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE code = %s AND id != %d',
                [$code, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE code = %s',
            [$code],
        );

        return ((int) $count) > 0;
    }

    /**
     * Active employees linked to a StaffCore Manager (hr_manager) account.
     */
    public function isEligibleDepartmentManager(int $employeeId): bool
    {
        $employeeTable = $this->db->resolveTableName('employee');
        $staffUserTable = $this->db->resolveTableName('staff_user');

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $employeeTable . '` AS e
             INNER JOIN `' . $staffUserTable . '` AS su ON su.platform_user_id = e.user_id
             WHERE e.id = %d
               AND e.employment_status = %s
               AND e.user_id IS NOT NULL
               AND e.user_id > 0
               AND su.role = %s
               AND su.status = %s',
            [
                $employeeId,
                Employee::STATUS_ACTIVE,
                RoleRegistry::ROLE_HR_MANAGER,
                StaffUser::STATUS_ACTIVE,
            ],
        );

        return ((int) $count) > 0;
    }

    public function isActiveEmployee(int $employeeId): bool
    {
        $table = $this->db->resolveTableName('employee');
        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '`
             WHERE id = %d AND employment_status = %s',
            [$employeeId, Employee::STATUS_ACTIVE],
        );

        return ((int) $count) > 0;
    }

    public function employeeExists(int $employeeId): bool
    {
        $table = $this->db->resolveTableName('employee');
        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE id = %d',
            [$employeeId],
        );

        return ((int) $count) > 0;
    }

    public function create(DepartmentInput $input, int $userId): Department
    {
        $id = $this->idSequence->nextId(self::TABLE);
        $now = gmdate('Y-m-d H:i:s');

        $data = array_merge($input->toDatabaseRow(), [
            'id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $ok = $this->db->insert(self::TABLE, $data);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to create department: ' . $this->db->getLastError()));
        }

        $department = $this->findById($id);

        if ($department === null) {
            throw new RuntimeException(esc_html('Department was created but could not be loaded.'));
        }

        return $department;
    }

    public function update(int $id, DepartmentInput $input, int $userId): Department
    {
        $data = array_merge($input->toDatabaseRow(), [
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);

        $ok = $this->db->update(
            self::TABLE,
            $data,
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update department: ' . $this->db->getLastError()));
        }

        $department = $this->findById($id);

        if ($department === null) {
            throw new RuntimeException(esc_html('Department was updated but could not be loaded.'));
        }

        return $department;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete department: ' . $this->db->getLastError()));
        }
    }
}


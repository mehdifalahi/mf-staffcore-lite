<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\Repositories;

use MF\StaffCore\Auth\PlatformUser;
use MF\StaffCore\Authorization\ScopeEmployeeIdsSql;
use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\Employees\DTO\EmployeeInput;
use MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter;
use MF\StaffCore\Modules\Positions\DTO\Position;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class EmployeeRepository
{
    private const TABLE = 'employee';

    private const SELECT_COLUMNS = 'e.id, e.code, e.department_id, e.position_id, e.first_name, e.last_name,
        e.email, e.phone, e.user_id, e.birth_date, e.hire_date, e.employment_status, e.employment_type,
        e.basic_salary, e.annual_leave, e.address, e.city, e.province, e.country, e.zip_code, e.photo, e.notes';

    private const JOIN_COLUMNS = ', d.name AS department_name, p.title AS position_title, p.level AS position_level,
        COALESCE(lr.used_leave_days, 0) AS used_leave_days';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Employee>
     */
    public function findPage(
        int $page,
        int $perPage,
        ?array $scopeEmployeeIds = null,
        ?EmployeeListFilter $filter = null,
    ): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $departmentTable = $this->db->resolveTableName('department');
        $positionTable = $this->db->resolveTableName('position');
        $leaveTable = $this->db->resolveTableName('leave_request');

        [$whereSql, $whereParams] = $this->buildFilterWhere($scopeEmployeeIds, $filter);

        $joinSql = ' INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             LEFT JOIN (
                SELECT employee_id, COALESCE(SUM(total_days), 0) AS used_leave_days
                FROM `' . $leaveTable . '`
                WHERE status = %s AND YEAR(start_date) = YEAR(CURDATE())
                GROUP BY employee_id
             ) AS lr ON lr.employee_id = e.id';

        $countParams = array_merge(['APPROVED'], $whereParams);
        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS e' . $joinSql . ' WHERE ' . $whereSql,
            $countParams,
        );

        $listParams = array_merge(['APPROVED'], $whereParams, [$perPage, $offset]);
        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_COLUMNS . '
             FROM `' . $table . '` AS e' . $joinSql . '
             WHERE ' . $whereSql . '
             ORDER BY e.id DESC
             LIMIT %d OFFSET %d',
            $listParams,
        );

        $items = array_map(
            static fn (array $row): Employee => Employee::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    public function findById(int $id, ?array $scopeEmployeeIds = null): ?Employee
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $departmentTable = $this->db->resolveTableName('department');
        $positionTable = $this->db->resolveTableName('position');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('e.id', $scopeEmployeeIds);

        $leaveTable = $this->db->resolveTableName('leave_request');
        $joinSql = ' INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             LEFT JOIN (
                SELECT employee_id, COALESCE(SUM(total_days), 0) AS used_leave_days
                FROM `' . $leaveTable . '`
                WHERE status = %s AND YEAR(start_date) = YEAR(CURDATE())
                GROUP BY employee_id
             ) AS lr ON lr.employee_id = e.id';

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_COLUMNS . '
             FROM `' . $table . '` AS e' . $joinSql . '
             WHERE e.id = %d' . $scopeSql . '
             LIMIT 1',
            array_merge(['APPROVED', $id], $scopeParams),
        );

        return $row !== null ? Employee::fromRow($row) : null;
    }

    public function findIdByPlatformUserId(int $platformUserId): ?int
    {
        if ($platformUserId <= 0) {
            return null;
        }

        $table = $this->db->resolveTableName(self::TABLE);

        $row = $this->db->getRow(
            'SELECT id FROM `' . $table . '` WHERE user_id = %d LIMIT 1',
            [$platformUserId],
        );

        if ($row === null) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @return array{id: int, label: string}|null
     */
    public function findManagerOptionByPlatformUserId(int $platformUserId): ?array
    {
        if ($platformUserId <= 0) {
            return null;
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $positionTable = $this->db->resolveTableName('position');

        $row = $this->db->getRow(
            'SELECT e.id, e.first_name, e.last_name, e.code, p.title AS position_title
             FROM `' . $table . '` AS e
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             WHERE e.user_id = %d AND e.employment_status = %s
             LIMIT 1',
            [$platformUserId, Employee::STATUS_ACTIVE],
        );

        return $row !== null ? $this->mapManagerOptionRow($row) : null;
    }

    /**
     * @return array{id: int, label: string}|null
     */
    public function findManagerOptionByEmail(string $email): ?array
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $positionTable = $this->db->resolveTableName('position');

        $row = $this->db->getRow(
            'SELECT e.id, e.first_name, e.last_name, e.code, p.title AS position_title
             FROM `' . $table . '` AS e
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             WHERE LOWER(e.email) = LOWER(%s) AND e.employment_status = %s
             LIMIT 1',
            [$email, Employee::STATUS_ACTIVE],
        );

        return $row !== null ? $this->mapManagerOptionRow($row) : null;
    }

    /**
     * @return array{id: int, label: string}|null
     */
    public function findManagerOptionById(int $employeeId): ?array
    {
        if ($employeeId <= 0) {
            return null;
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $positionTable = $this->db->resolveTableName('position');

        $row = $this->db->getRow(
            'SELECT e.id, e.first_name, e.last_name, e.code, p.title AS position_title
             FROM `' . $table . '` AS e
             INNER JOIN `' . $positionTable . '` AS p ON p.id = e.position_id
             WHERE e.id = %d
             LIMIT 1',
            [$employeeId],
        );

        return $row !== null ? $this->mapManagerOptionRow($row) : null;
    }

    public function linkPlatformUser(int $employeeId, int $platformUserId, int $actorUserId): void
    {
        if ($employeeId <= 0 || $platformUserId <= 0) {
            return;
        }

        $this->db->update(
            self::TABLE,
            [
                'user_id' => $platformUserId,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $actorUserId,
            ],
            ['id' => $employeeId],
        );
    }

    public function createForPlatformManager(
        PlatformUser $platformUser,
        int $departmentId,
        int $positionId,
        int $actorUserId,
    ): int {
        return $this->createForPlatformUser(
            $platformUser,
            $departmentId,
            $positionId,
            $actorUserId,
            'MGR',
        );
    }

    public function createForPlatformEmployee(
        PlatformUser $platformUser,
        int $departmentId,
        int $positionId,
        int $actorUserId,
    ): int {
        return $this->createForPlatformUser(
            $platformUser,
            $departmentId,
            $positionId,
            $actorUserId,
            'EMP',
        );
    }

    public function firstActiveDepartmentId(): ?int
    {
        $departmentTable = $this->db->resolveTableName('department');

        $row = $this->db->getRow(
            'SELECT id FROM `' . $departmentTable . '`
             WHERE status = %s
             ORDER BY id ASC
             LIMIT 1',
            [Department::STATUS_ACTIVE],
        );

        if ($row === null) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function firstActivePositionIdForDepartment(int $departmentId): ?int
    {
        if ($departmentId <= 0) {
            return null;
        }

        $positionTable = $this->db->resolveTableName('position');
        $row = $this->db->getRow(
            'SELECT id FROM `' . $positionTable . '`
             WHERE department_id = %d AND status = %s
             ORDER BY id ASC
             LIMIT 1',
            [$departmentId, Position::STATUS_ACTIVE],
        );

        if ($row === null) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function firstActivePositionId(): ?int
    {
        $positionTable = $this->db->resolveTableName('position');
        $row = $this->db->getRow(
            'SELECT id FROM `' . $positionTable . '`
             WHERE status = %s
             ORDER BY id ASC
             LIMIT 1',
            [Position::STATUS_ACTIVE],
        );

        if ($row === null) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function createForPlatformUser(
        PlatformUser $platformUser,
        int $departmentId,
        int $positionId,
        int $actorUserId,
        string $codePrefix,
    ): int {
        [$firstName, $lastName] = $this->splitDisplayName($platformUser->displayName, $platformUser->login);
        $code = $this->generateUniqueUserCode($codePrefix, $platformUser->id);

        $input = new EmployeeInput(
            code: $code,
            departmentId: $departmentId,
            positionId: $positionId,
            firstName: $firstName,
            lastName: $lastName,
            email: $platformUser->email !== '' ? $platformUser->email : ($platformUser->login . '@local'),
            phone: null,
            userId: $platformUser->id,
            birthDate: null,
            hireDate: gmdate('Y-m-d'),
            employmentStatus: Employee::STATUS_ACTIVE,
            employmentType: Employee::TYPE_FULLTIME,
            basicSalary: null,
            annualLeave: 0,
            address: null,
            city: null,
            province: null,
            country: null,
            zipCode: null,
            photo: null,
            notes: null,
            allowances: [],
        );

        $employee = $this->create($input, $actorUserId);

        return $employee->id;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, label: string}|null
     */
    private function mapManagerOptionRow(array $row): ?array
    {
        $id = (int) ($row['id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        $code = trim((string) ($row['code'] ?? ''));
        $positionTitle = trim((string) ($row['position_title'] ?? ''));

        $label = $name;

        if ($code !== '') {
            $label .= ' (' . $code . ')';
        }

        if ($positionTitle !== '') {
            $label .= ' — ' . $positionTitle;
        }

        return [
            'id' => $id,
            'label' => $label,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDisplayName(string $displayName, string $login): array
    {
        $displayName = trim($displayName);

        if ($displayName !== '') {
            $parts = preg_split('/\s+/', $displayName, 2) ?: [];

            return [
                (string) ($parts[0] ?? $login),
                (string) ($parts[1] ?? ''),
            ];
        }

        return [$login, ''];
    }

    private function generateUniqueUserCode(string $prefix, int $platformUserId): string
    {
        $normalizedPrefix = preg_replace('/[^A-Z0-9]/', '', strtoupper($prefix)) ?: 'EMP';
        $base = $normalizedPrefix . max(1, $platformUserId);
        $code = $base;
        $suffix = 1;

        while ($this->codeExists($code)) {
            $code = $base . '-' . $suffix;
            ++$suffix;
        }

        return $code;
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

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE email = %s AND id != %d',
                [$email, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE email = %s',
            [$email],
        );

        return ((int) $count) > 0;
    }

    public function userIdExists(int $userId, ?int $excludeEmployeeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeEmployeeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE user_id = %d AND id != %d',
                [$userId, $excludeEmployeeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE user_id = %d',
            [$userId],
        );

        return ((int) $count) > 0;
    }

    /**
     * @return list<int>
     */
    public function findLinkedUserIds(?int $excludeEmployeeId = null): array
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeEmployeeId !== null) {
            $rows = $this->db->getResults(
                'SELECT user_id FROM `' . $table . '`
                 WHERE user_id IS NOT NULL AND id != %d',
                [$excludeEmployeeId],
            );
        } else {
            $rows = $this->db->getResults(
                'SELECT user_id FROM `' . $table . '`
                 WHERE user_id IS NOT NULL',
            );
        }

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function positionBelongsToDepartment(int $positionId, int $departmentId): bool
    {
        $positionTable = $this->db->resolveTableName('position');
        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $positionTable . '`
             WHERE id = %d AND department_id = %d',
            [$positionId, $departmentId],
        );

        return ((int) $count) > 0;
    }

    public function create(EmployeeInput $input, int $userId): Employee
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
            throw new RuntimeException(esc_html('Failed to create employee: ' . $this->db->getLastError()));
        }

        $employee = $this->findById($id);

        if ($employee === null) {
            throw new RuntimeException(esc_html('Employee was created but could not be loaded.'));
        }

        return $employee;
    }

    public function update(int $id, EmployeeInput $input, int $userId): Employee
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
            throw new RuntimeException(esc_html('Failed to update employee: ' . $this->db->getLastError()));
        }

        $employee = $this->findById($id);

        if ($employee === null) {
            throw new RuntimeException(esc_html('Employee was updated but could not be loaded.'));
        }

        return $employee;
    }

    public function updatePhoto(int $id, string $photoUrl, int $userId): Employee
    {
        $ok = $this->db->update(
            self::TABLE,
            [
                'photo' => $photoUrl,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ],
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update employee photo: ' . $this->db->getLastError()));
        }

        $employee = $this->findById($id);

        if ($employee === null) {
            throw new RuntimeException(esc_html('Employee photo was updated but the record could not be loaded.'));
        }

        return $employee;
    }

    public function clearPhoto(int $id, int $userId): Employee
    {
        $ok = $this->db->update(
            self::TABLE,
            [
                'photo' => null,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ],
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to remove employee photo: ' . $this->db->getLastError()));
        }

        $employee = $this->findById($id);

        if ($employee === null) {
            throw new RuntimeException(esc_html('Employee photo was removed but the record could not be loaded.'));
        }

        return $employee;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete employee: ' . $this->db->getLastError()));
        }
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?array $scopeEmployeeIds, ?EmployeeListFilter $filter): array
    {
        $clauses = ['1=1'];
        $params = [];

        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::whereIn('e.id', $scopeEmployeeIds);

        if ($scopeSql !== '') {
            $clauses[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if ($filter === null) {
            return [implode(' AND ', $clauses), $params];
        }

        if ($filter->departmentId !== null) {
            $clauses[] = 'e.department_id = %d';
            $params[] = $filter->departmentId;
        }

        if ($filter->employmentStatus !== null) {
            $clauses[] = 'e.employment_status = %s';
            $params[] = $filter->employmentStatus;
        }

        if ($filter->search !== null) {
            $like = '%' . $this->escapeLike($filter->search) . '%';
            $clauses[] = '(e.code LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return [implode(' AND ', $clauses), $params];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Employees with platform user linked, excluding IDs already in staff_user.
     *
     * @param list<int> $excludePlatformUserIds
     * @return PaginatedList<array{platform_user_id: int, employee_id: int, department_name: string|null}>
     */
    public function findUnlinkedWithEmployeePage(
        array $excludePlatformUserIds,
        ?string $search,
        int $page,
        int $perPage,
    ): PaginatedList {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $table = $this->db->resolveTableName(self::TABLE);
        $departmentTable = $this->db->resolveTableName('department');

        $where = ['e.user_id IS NOT NULL', 'e.user_id > 0'];
        $params = [];

        if ($excludePlatformUserIds !== []) {
            $placeholders = implode(',', array_fill(0, count($excludePlatformUserIds), '%d'));
            $where[] = 'e.user_id NOT IN (' . $placeholders . ')';
            $params = array_merge($params, $excludePlatformUserIds);
        }

        if ($search !== null && trim($search) !== '') {
            $like = '%' . $this->escapeLike(trim($search)) . '%';
            $where[] = '(e.first_name LIKE %s OR e.last_name LIKE %s OR e.email LIKE %s OR e.code LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             WHERE ' . $whereSql,
            $params,
        );

        $listParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->db->getResults(
            'SELECT e.user_id AS platform_user_id, e.id AS employee_id, d.name AS department_name
             FROM `' . $table . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             WHERE ' . $whereSql . '
             ORDER BY e.user_id DESC
             LIMIT %d OFFSET %d',
            $listParams,
        );

        /** @var list<array{platform_user_id: int, employee_id: int, department_name: string|null}> $items */
        $items = array_map(
            static function (array $row): array {
                return [
                    'platform_user_id' => (int) ($row['platform_user_id'] ?? 0),
                    'employee_id' => (int) ($row['employee_id'] ?? 0),
                    'department_name' => isset($row['department_name']) && $row['department_name'] !== ''
                        ? (string) $row['department_name']
                        : null,
                ];
            },
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @param list<int> $platformUserIds
     * @return array<int, array{employee_id: int, department_name: string|null}>
     */
    public function findEmployeeMetaByPlatformUserIds(array $platformUserIds): array
    {
        $ids = array_values(array_filter(
            array_map('intval', $platformUserIds),
            static fn (int $id): bool => $id > 0,
        ));

        if ($ids === []) {
            return [];
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $departmentTable = $this->db->resolveTableName('department');
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $this->db->getResults(
            'SELECT e.user_id AS platform_user_id, e.id AS employee_id, d.name AS department_name
             FROM `' . $table . '` AS e
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = e.department_id
             WHERE e.user_id IN (' . $placeholders . ')',
            $ids,
        );

        $map = [];

        foreach ($rows as $row) {
            $platformUserId = (int) ($row['platform_user_id'] ?? 0);

            if ($platformUserId <= 0) {
                continue;
            }

            $map[$platformUserId] = [
                'employee_id' => (int) ($row['employee_id'] ?? 0),
                'department_name' => isset($row['department_name']) && $row['department_name'] !== ''
                    ? (string) $row['department_name']
                    : null,
            ];
        }

        return $map;
    }

    /**
     * @param list<int> $platformUserIds
     * @return array<int, string> platform_user_id => photo URL
     */
    public function findPhotosByPlatformUserIds(array $platformUserIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $platformUserIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            return [];
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $this->db->getResults(
            'SELECT user_id, photo FROM `' . $table . '`
             WHERE user_id IN (' . $placeholders . ') AND photo IS NOT NULL AND photo != %s',
            array_merge($ids, ['']),
        );

        $map = [];

        foreach ($rows as $row) {
            $platformUserId = (int) ($row['user_id'] ?? 0);
            $photo = isset($row['photo']) ? trim((string) $row['photo']) : '';

            if ($platformUserId > 0 && $photo !== '') {
                $map[$platformUserId] = $photo;
            }
        }

        return $map;
    }

    public function countAll(): int
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $count = $this->db->getVar('SELECT COUNT(*) FROM `' . $table . '`');

        return (int) $count;
    }
}


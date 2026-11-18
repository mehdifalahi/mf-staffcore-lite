<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Attendance\Repositories;

use MF\StaffCore\Authorization\ScopeEmployeeIdsSql;
use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Attendance\DTO\AttendanceInput;
use MF\StaffCore\Modules\Attendance\DTO\AttendanceListFilter;
use MF\StaffCore\Modules\Attendance\DTO\EmployeeAttendanceMonthSummary;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class AttendanceRepository
{
    private const TABLE = 'attendance';

    private const SELECT_COLUMNS = 'a.id, a.employee_id, a.attendance_date, a.clock_in, a.clock_out,
        a.status, a.attendance_type, a.remarks, a.created_at, a.updated_at, a.created_by, a.updated_by';

    private const JOIN_EMPLOYEE = ", TRIM(CONCAT(e.first_name, ' ', e.last_name)) AS employee_name,
        e.code AS employee_code, e.photo AS employee_photo";

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Attendance>
     */
    public function findPage(
        int $page,
        int $perPage,
        ?array $scopeEmployeeIds = null,
        ?AttendanceListFilter $filter = null,
    ): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');
        [$whereSql, $whereParams] = $this->buildFilterWhere($scopeEmployeeIds, $filter);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS a WHERE ' . $whereSql,
            $whereParams,
        );

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_EMPLOYEE . '
             FROM `' . $table . '` AS a
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = a.employee_id
             WHERE ' . $whereSql . '
             ORDER BY a.attendance_date DESC, a.id DESC
             LIMIT %d OFFSET %d',
            array_merge($whereParams, [$perPage, $offset]),
        );

        $items = array_map(
            static fn (array $row): Attendance => Attendance::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    public function findById(int $id, ?array $scopeEmployeeIds = null): ?Attendance
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('a.employee_id', $scopeEmployeeIds);

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_EMPLOYEE . '
             FROM `' . $table . '` AS a
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = a.employee_id
             WHERE a.id = %d' . $scopeSql . '
             LIMIT 1',
            array_merge([$id], $scopeParams),
        );

        return $row !== null ? Attendance::fromRow($row) : null;
    }

    public function findByEmployeeAndDate(int $employeeId, string $date): ?Attendance
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '` AS a
             WHERE a.employee_id = %d AND a.attendance_date = %s
             LIMIT 1',
            [$employeeId, $date],
        );

        return $row !== null ? Attendance::fromRow($row) : null;
    }

    public function employeeAndDateExists(int $employeeId, string $date, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '`
                 WHERE employee_id = %d AND attendance_date = %s AND id != %d',
                [$employeeId, $date, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '`
             WHERE employee_id = %d AND attendance_date = %s',
            [$employeeId, $date],
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

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return list<array{id: int, label: string}>
     */
    public function findEmployeeOptions(?array $scopeEmployeeIds = null): array
    {
        if ($scopeEmployeeIds !== null) {
            if ($scopeEmployeeIds === []) {
                return [];
            }

            if (count($scopeEmployeeIds) === 1) {
                return $this->findEmployeeOptionsForId($scopeEmployeeIds[0]);
            }

            return $this->findEmployeeOptionsForIds($scopeEmployeeIds);
        }

        $table = $this->db->resolveTableName('employee');
        $rows = $this->db->getResults(
            'SELECT id, first_name, last_name, code
             FROM `' . $table . '`
             WHERE employment_status = %s
             ORDER BY first_name ASC, last_name ASC',
            ['ACTIVE'],
        );

        return $this->mapEmployeeOptionRows($rows);
    }

    public function create(AttendanceInput $input, int $userId): Attendance
    {
        return $this->insertAttendance($input, $userId, $userId);
    }

    public function createSystem(AttendanceInput $input): Attendance
    {
        return $this->insertAttendance($input, null, null);
    }

    private function insertAttendance(AttendanceInput $input, ?int $createdBy, ?int $updatedBy): Attendance
    {
        $id = $this->idSequence->nextId(self::TABLE);
        $now = gmdate('Y-m-d H:i:s');

        $data = array_merge($input->toDatabaseRow(), [
            'id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $createdBy,
            'updated_by' => $updatedBy,
        ]);

        $ok = $this->db->insert(self::TABLE, $data);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to create attendance record: ' . $this->db->getLastError()));
        }

        $attendance = $this->findById($id);

        if ($attendance === null) {
            throw new RuntimeException(esc_html('Attendance record was created but could not be loaded.'));
        }

        return $attendance;
    }

    public function update(int $id, AttendanceInput $input, int $userId): Attendance
    {
        return $this->updateAttendance($id, $input, $userId);
    }

    public function updateSystem(int $id, AttendanceInput $input): Attendance
    {
        return $this->updateAttendance($id, $input, null);
    }

    private function updateAttendance(int $id, AttendanceInput $input, ?int $updatedBy): Attendance
    {
        $data = array_merge($input->toDatabaseRow(), [
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => $updatedBy,
        ]);

        $ok = $this->db->update(
            self::TABLE,
            $data,
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update attendance record: ' . $this->db->getLastError()));
        }

        $attendance = $this->findById($id);

        if ($attendance === null) {
            throw new RuntimeException(esc_html('Attendance record was updated but could not be loaded.'));
        }

        return $attendance;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete attendance record: ' . $this->db->getLastError()));
        }
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function findEmployeeOptionsForId(int $employeeId): array
    {
        $table = $this->db->resolveTableName('employee');
        $row = $this->db->getRow(
            'SELECT id, first_name, last_name, code
             FROM `' . $table . '`
             WHERE id = %d AND employment_status = %s
             LIMIT 1',
            [$employeeId, 'ACTIVE'],
        );

        if ($row === null) {
            return [];
        }

        $id = (int) ($row['id'] ?? 0);

        if ($id <= 0) {
            return [];
        }

        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        $code = (string) ($row['code'] ?? '');
        $label = $code !== '' ? $name . ' (' . $code . ')' : $name;

        return [
            [
                'id' => $id,
                'label' => $label,
            ],
        ];
    }

    /**
     * @param list<int> $employeeIds
     * @return list<array{id: int, label: string}>
     */
    private function findEmployeeOptionsForIds(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $table = $this->db->resolveTableName('employee');
        $placeholders = implode(', ', array_fill(0, count($employeeIds), '%d'));
        $rows = $this->db->getResults(
            'SELECT id, first_name, last_name, code
             FROM `' . $table . '`
             WHERE employment_status = %s
               AND id IN (' . $placeholders . ')
             ORDER BY first_name ASC, last_name ASC',
            array_merge(['ACTIVE'], $employeeIds),
        );

        return $this->mapEmployeeOptionRows($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{id: int, label: string}>
     */
    private function mapEmployeeOptionRows(array $rows): array
    {
        $options = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $code = (string) ($row['code'] ?? '');
            $label = $code !== '' ? $name . ' (' . $code . ')' : $name;

            $options[] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        return $options;
    }

    public function employeeMonthSummary(int $employeeId, int $year, int $month): EmployeeAttendanceMonthSummary
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT status, clock_in, clock_out
             FROM `' . $table . '`
             WHERE employee_id = %d
             AND YEAR(attendance_date) = %d
             AND MONTH(attendance_date) = %d',
            [$employeeId, $year, $month],
        );

        $totalDays = count($rows);
        $presentDays = 0;
        $lateDays = 0;
        $totalHours = 0.0;

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');

            if ($status === Attendance::STATUS_PRESENT) {
                ++$presentDays;
            }

            if ($status === Attendance::STATUS_LATE) {
                ++$lateDays;
            }

            $totalHours += $this->hoursBetween(
                isset($row['clock_in']) ? (string) $row['clock_in'] : null,
                isset($row['clock_out']) ? (string) $row['clock_out'] : null,
            );
        }

        return new EmployeeAttendanceMonthSummary(
            totalDays: $totalDays,
            presentDays: $presentDays,
            lateDays: $lateDays,
            totalHours: round($totalHours, 1),
        );
    }

    private function hoursBetween(?string $clockIn, ?string $clockOut): float
    {
        if ($clockIn === null || $clockOut === null || $clockIn === '' || $clockOut === '') {
            return 0.0;
        }

        $in = strtotime($clockIn);
        $out = strtotime($clockOut);

        if ($in === false || $out === false || $out <= $in) {
            return 0.0;
        }

        return ($out - $in) / 3600;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?array $scopeEmployeeIds, ?AttendanceListFilter $filter): array
    {
        $clauses = ['1=1'];
        $params = [];

        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::whereIn('a.employee_id', $scopeEmployeeIds);

        if ($scopeSql !== '') {
            $clauses[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if ($filter === null) {
            return [implode(' AND ', $clauses), $params];
        }

        if ($filter->employeeId !== null) {
            $clauses[] = 'a.employee_id = %d';
            $params[] = $filter->employeeId;
        }

        if ($filter->status !== null) {
            $clauses[] = 'a.status = %s';
            $params[] = $filter->status;
        }

        if ($filter->dateFrom !== null) {
            $clauses[] = 'a.attendance_date >= %s';
            $params[] = $filter->dateFrom;
        }

        if ($filter->dateTo !== null) {
            $clauses[] = 'a.attendance_date <= %s';
            $params[] = $filter->dateTo;
        }

        return [implode(' AND ', $clauses), $params];
    }
}


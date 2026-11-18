<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\LeaveRequests\Repositories;

use MF\StaffCore\Authorization\ScopeEmployeeIdsSql;
use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestInput;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestListFilter;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class LeaveRequestRepository
{
    private const TABLE = 'leave_request';

    private const SELECT_COLUMNS = 'lr.id, lr.employee_id, lr.leave_type, lr.start_date, lr.end_date,
        lr.total_days, lr.reason, lr.notes, lr.status, lr.created_at, lr.updated_at,
        lr.created_by, lr.updated_by';

    private const JOIN_EMPLOYEE = ", TRIM(CONCAT(e.first_name, ' ', e.last_name)) AS employee_name,
        e.code AS employee_code, e.photo AS employee_photo";

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<LeaveRequest>
     */
    public function findPage(
        int $page,
        int $perPage,
        ?array $scopeEmployeeIds = null,
        ?LeaveRequestListFilter $filter = null,
    ): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');
        [$whereSql, $whereParams] = $this->buildFilterWhere($scopeEmployeeIds, $filter);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS lr WHERE ' . $whereSql,
            $whereParams,
        );

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_EMPLOYEE . '
             FROM `' . $table . '` AS lr
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = lr.employee_id
             WHERE ' . $whereSql . '
             ORDER BY lr.start_date DESC, lr.id DESC
             LIMIT %d OFFSET %d',
            array_merge($whereParams, [$perPage, $offset]),
        );

        $items = array_map(
            static fn (array $row): LeaveRequest => LeaveRequest::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    public function findById(int $id, ?array $scopeEmployeeIds = null): ?LeaveRequest
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('lr.employee_id', $scopeEmployeeIds);

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_EMPLOYEE . '
             FROM `' . $table . '` AS lr
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = lr.employee_id
             WHERE lr.id = %d' . $scopeSql . '
             LIMIT 1',
            array_merge([$id], $scopeParams),
        );

        return $row !== null ? LeaveRequest::fromRow($row) : null;
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

    public function findEmployeeAnnualLeave(int $employeeId): int
    {
        $table = $this->db->resolveTableName('employee');
        $value = $this->db->getVar(
            'SELECT annual_leave FROM `' . $table . '` WHERE id = %d',
            [$employeeId],
        );

        return (int) $value;
    }

    /**
     * Approved annual-leave days consumed in a calendar year (Sick/Unpaid/etc. excluded).
     */
    public function findApprovedLeaveDaysForYear(int $employeeId, int $year, ?int $excludeId = null): int
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $value = $this->db->getVar(
                'SELECT COALESCE(SUM(total_days), 0) FROM `' . $table . '`
                 WHERE employee_id = %d AND status = %s AND leave_type = %s AND YEAR(start_date) = %d AND id != %d',
                [$employeeId, LeaveRequest::STATUS_APPROVED, LeaveRequest::TYPE_ANNUAL, $year, $excludeId],
            );

            return (int) $value;
        }

        $value = $this->db->getVar(
            'SELECT COALESCE(SUM(total_days), 0) FROM `' . $table . '`
             WHERE employee_id = %d AND status = %s AND leave_type = %s AND YEAR(start_date) = %d',
            [$employeeId, LeaveRequest::STATUS_APPROVED, LeaveRequest::TYPE_ANNUAL, $year],
        );

        return (int) $value;
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

    public function create(LeaveRequestInput $input, int $userId): LeaveRequest
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
            throw new RuntimeException(esc_html('Failed to create leave request: ' . $this->db->getLastError()));
        }

        $leaveRequest = $this->findById($id);

        if ($leaveRequest === null) {
            throw new RuntimeException(esc_html('Leave request was created but could not be loaded.'));
        }

        return $leaveRequest;
    }

    public function update(int $id, LeaveRequestInput $input, int $userId): LeaveRequest
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
            throw new RuntimeException(esc_html('Failed to update leave request: ' . $this->db->getLastError()));
        }

        $leaveRequest = $this->findById($id);

        if ($leaveRequest === null) {
            throw new RuntimeException(esc_html('Leave request was updated but could not be loaded.'));
        }

        return $leaveRequest;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete leave request: ' . $this->db->getLastError()));
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

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?array $scopeEmployeeIds, ?LeaveRequestListFilter $filter): array
    {
        $clauses = ['1=1'];
        $params = [];

        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::whereIn('lr.employee_id', $scopeEmployeeIds);

        if ($scopeSql !== '') {
            $clauses[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if ($filter === null) {
            return [implode(' AND ', $clauses), $params];
        }

        if ($filter->employeeId !== null) {
            $clauses[] = 'lr.employee_id = %d';
            $params[] = $filter->employeeId;
        }

        if ($filter->status !== null) {
            $clauses[] = 'lr.status = %s';
            $params[] = $filter->status;
        }

        if ($filter->leaveType !== null) {
            $clauses[] = 'lr.leave_type = %s';
            $params[] = $filter->leaveType;
        }

        if ($filter->dateFrom !== null) {
            $clauses[] = 'lr.start_date >= %s';
            $params[] = $filter->dateFrom;
        }

        if ($filter->dateTo !== null) {
            $clauses[] = 'lr.start_date <= %s';
            $params[] = $filter->dateTo;
        }

        return [implode(' AND ', $clauses), $params];
    }
}


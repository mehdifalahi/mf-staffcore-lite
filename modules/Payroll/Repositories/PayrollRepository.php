<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Payroll\Repositories;

use MF\StaffCore\Authorization\ScopeEmployeeIdsSql;
use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Payroll\DTO\Payroll;
use MF\StaffCore\Modules\Payroll\DTO\PayrollInput;
use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Modules\MyPayslips\DTO\EmployeePayslipSummary;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class PayrollRepository
{
    private const TABLE = 'payroll';

    private const SELECT_COLUMNS = 'p.id, p.employee_id, p.payroll_month, p.payroll_year, p.basic_salary,
        p.allowances, p.overtime_amount, p.bonus, p.commission, p.tax, p.insurance, p.advance_deduction,
        p.other_deductions, p.working_days, p.present_days, p.absent_days, p.leave_days, p.overtime_hours,
        p.payment_status, p.payment_date, p.payment_method, p.payment_reference, p.notes,
        p.created_at, p.updated_at, p.created_by, p.updated_by';

    private const JOIN_EMPLOYEE = ", TRIM(CONCAT(e.first_name, ' ', e.last_name)) AS employee_name,
        e.code AS employee_code, e.photo AS employee_photo";

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return PaginatedList<Payroll>
     */
    public function findPage(
        int $page,
        int $perPage,
        ?array $scopeEmployeeIds = null,
        ?PayrollListFilter $filter = null,
    ): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');

        [$whereSql, $whereParams] = $this->buildFilterWhere($scopeEmployeeIds, $filter);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS p WHERE ' . $whereSql,
            $whereParams,
        );

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_EMPLOYEE . '
             FROM `' . $table . '` AS p
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = p.employee_id
             WHERE ' . $whereSql . '
             ORDER BY p.payroll_year DESC, p.payroll_month DESC, p.id DESC
             LIMIT %d OFFSET %d',
            array_merge($whereParams, [$perPage, $offset]),
        );

        $items = array_map(
            static fn (array $row): Payroll => Payroll::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function findById(int $id, ?array $scopeEmployeeIds = null): ?Payroll
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('p.employee_id', $scopeEmployeeIds);

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_EMPLOYEE . '
             FROM `' . $table . '` AS p
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = p.employee_id
             WHERE p.id = %d' . $scopeSql . '
             LIMIT 1',
            array_merge([$id], $scopeParams),
        );

        return $row !== null ? Payroll::fromRow($row) : null;
    }

    /**
     * @return list<int>
     */
    public function distinctYearsForEmployee(int $employeeId): array
    {
        if ($employeeId <= 0) {
            return [];
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT DISTINCT payroll_year
             FROM `' . $table . '`
             WHERE employee_id = %d
             ORDER BY payroll_year DESC',
            [$employeeId],
        );

        $years = [];

        foreach ($rows as $row) {
            $year = (int) ($row['payroll_year'] ?? 0);

            if ($year > 0) {
                $years[] = $year;
            }
        }

        return $years;
    }

    public function employeePayslipSummary(int $employeeId, int $currentYear): EmployeePayslipSummary
    {
        if ($employeeId <= 0) {
            return new EmployeePayslipSummary();
        }

        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT basic_salary, allowances, overtime_amount, bonus, commission,
                tax, insurance, advance_deduction, other_deductions,
                payment_status, payroll_year
             FROM `' . $table . '`
             WHERE employee_id = %d',
            [$employeeId],
        );

        $totalPayslips = count($rows);
        $thisYearEarnings = 0.0;
        $totalPaidEarnings = 0.0;

        foreach ($rows as $row) {
            $netPay = $this->netPayFromSalaryRow($row);
            $status = (string) ($row['payment_status'] ?? '');
            $year = (int) ($row['payroll_year'] ?? 0);

            if ($status === Payroll::STATUS_PAID) {
                $totalPaidEarnings += $netPay;

                if ($year === $currentYear) {
                    $thisYearEarnings += $netPay;
                }
            }
        }

        return new EmployeePayslipSummary(
            totalPayslips: $totalPayslips,
            thisYearEarnings: round($thisYearEarnings, 2),
            totalPaidEarnings: round($totalPaidEarnings, 2),
        );
    }

    public function employeePeriodExists(int $employeeId, int $year, int $month, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '`
                 WHERE employee_id = %d AND payroll_year = %d AND payroll_month = %d AND id != %d',
                [$employeeId, $year, $month, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '`
             WHERE employee_id = %d AND payroll_year = %d AND payroll_month = %d',
            [$employeeId, $year, $month],
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
        if ($scopeEmployeeIds !== null && $scopeEmployeeIds === []) {
            return [];
        }

        $table = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::whereIn('id', $scopeEmployeeIds);
        $where = 'employment_status = %s' . ($scopeSql !== '' ? ' AND ' . $scopeSql : '');
        $params = array_merge(['ACTIVE'], $scopeParams);

        $rows = $this->db->getResults(
            'SELECT id, first_name, last_name, code
             FROM `' . $table . '`
             WHERE ' . $where . '
             ORDER BY first_name ASC, last_name ASC',
            $params,
        );

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
     * @return array{
     *   basic_salary: float,
     *   allowances: float,
     *   working_days: int,
     *   present_days: int,
     *   absent_days: int,
     *   leave_days: int
     * }
     */
    public function findPrefillData(int $employeeId, int $year, int $month): array
    {
        $employeeTable = $this->db->resolveTableName('employee');
        $allowanceTable = $this->db->resolveTableName('employee_allowance');
        $attendanceTable = $this->db->resolveTableName('attendance');

        $employeeRow = $this->db->getRow(
            'SELECT basic_salary FROM `' . $employeeTable . '` WHERE id = %d',
            [$employeeId],
        );

        $basicSalary = (float) ($employeeRow['basic_salary'] ?? 0);

        $allowances = (float) $this->db->getVar(
            'SELECT COALESCE(SUM(amount), 0) FROM `' . $allowanceTable . '` WHERE employee_id = %d',
            [$employeeId],
        );

        $attendanceRow = $this->db->getRow(
            'SELECT
                SUM(CASE WHEN status IN (%s, %s) THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS leave_days
             FROM `' . $attendanceTable . '`
             WHERE employee_id = %d AND YEAR(attendance_date) = %d AND MONTH(attendance_date) = %d',
            [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_ON_LEAVE,
                $employeeId,
                $year,
                $month,
            ],
        );

        $presentDays = (int) ($attendanceRow['present_days'] ?? 0);
        $absentDays = (int) ($attendanceRow['absent_days'] ?? 0);
        $leaveDays = (int) ($attendanceRow['leave_days'] ?? 0);
        $workingDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        return [
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'leave_days' => $leaveDays,
        ];
    }

    public function create(PayrollInput $input, int $userId): Payroll
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
            throw new RuntimeException(esc_html('Failed to create payroll record: ' . $this->db->getLastError()));
        }

        $payroll = $this->findById($id);

        if ($payroll === null) {
            throw new RuntimeException(esc_html('Payroll record was created but could not be loaded.'));
        }

        return $payroll;
    }

    public function update(int $id, PayrollInput $input, int $userId): Payroll
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
            throw new RuntimeException(esc_html('Failed to update payroll record: ' . $this->db->getLastError()));
        }

        $payroll = $this->findById($id);

        if ($payroll === null) {
            throw new RuntimeException(esc_html('Payroll record was updated but could not be loaded.'));
        }

        return $payroll;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete payroll record: ' . $this->db->getLastError()));
        }
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?array $scopeEmployeeIds, ?PayrollListFilter $filter): array
    {
        $clauses = ['1=1'];
        $params = [];

        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::whereIn('p.employee_id', $scopeEmployeeIds);

        if ($scopeSql !== '') {
            $clauses[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if ($filter === null) {
            return [implode(' AND ', $clauses), $params];
        }

        if ($filter->employeeId !== null) {
            $clauses[] = 'p.employee_id = %d';
            $params[] = $filter->employeeId;
        }

        if ($filter->paymentStatus !== null) {
            $clauses[] = 'p.payment_status = %s';
            $params[] = $filter->paymentStatus;
        }

        if ($filter->year !== null) {
            $clauses[] = 'p.payroll_year = %d';
            $params[] = $filter->year;
        }

        if ($filter->month !== null) {
            $clauses[] = 'p.payroll_month = %d';
            $params[] = $filter->month;
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function netPayFromSalaryRow(array $row): float
    {
        $gross = (float) ($row['basic_salary'] ?? 0)
            + (float) ($row['allowances'] ?? 0)
            + (float) ($row['overtime_amount'] ?? 0)
            + (float) ($row['bonus'] ?? 0)
            + (float) ($row['commission'] ?? 0);
        $deductions = (float) ($row['tax'] ?? 0)
            + (float) ($row['insurance'] ?? 0)
            + (float) ($row['advance_deduction'] ?? 0)
            + (float) ($row['other_deductions'] ?? 0);

        return max(0.0, $gross - $deductions);
    }
}


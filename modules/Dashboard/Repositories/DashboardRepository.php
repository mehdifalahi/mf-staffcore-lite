<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard\Repositories;

use MF\StaffCore\Authorization\ScopeEmployeeIdsSql;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Dashboard\DTO\DashboardDepartmentCard;
use MF\StaffCore\Modules\Dashboard\DTO\DashboardOverviewStats;
use MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardStats;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Modules\Payroll\DTO\Payroll;

final class DashboardRepository
{
    public function __construct(
        private readonly TableGateway $db,
    ) {
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function stats(?array $scopeEmployeeIds, string $today): DashboardOverviewStats
    {
        return new DashboardOverviewStats(
            totalEmployees: $this->countEmployees($scopeEmployeeIds),
            activeEmployees: $this->countEmployees($scopeEmployeeIds, Employee::STATUS_ACTIVE),
            attendanceToday: $this->countAttendanceToday($scopeEmployeeIds, $today),
            onLeaveToday: $this->countOnLeaveToday($scopeEmployeeIds, $today),
            departments: $scopeEmployeeIds === null ? $this->countTable('department') : 0,
            pendingLeaves: $this->countPendingLeaves($scopeEmployeeIds),
            totalAssets: $scopeEmployeeIds === null ? $this->countTable('assets') : 0,
            payrollRecords: $this->countPayrollRecords($scopeEmployeeIds),
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return list<Attendance>
     */
    public function recentAttendance(int $limit, ?array $scopeEmployeeIds): array
    {
        $limit = max(1, min(20, $limit));
        $table = $this->db->resolveTableName('attendance');
        $employeeTable = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('a.employee_id', $scopeEmployeeIds);

        $rows = $this->db->getResults(
            'SELECT a.id, a.employee_id, a.attendance_date, a.clock_in, a.clock_out,
                a.status, a.attendance_type, a.remarks, a.created_at, a.updated_at,
                a.created_by, a.updated_by,
                TRIM(CONCAT(e.first_name, \' \', e.last_name)) AS employee_name,
                e.code AS employee_code,
                e.photo AS employee_photo
             FROM `' . $table . '` AS a
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = a.employee_id
             WHERE 1=1' . $scopeSql . '
             ORDER BY a.attendance_date DESC, a.id DESC
             LIMIT %d',
            array_merge($scopeParams, [$limit]),
        );

        return array_map(
            static fn (array $row): Attendance => Attendance::fromRow($row),
            $rows,
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return list<LeaveRequest>
     */
    public function pendingLeaveRequests(int $limit, ?array $scopeEmployeeIds): array
    {
        $limit = max(1, min(20, $limit));
        $table = $this->db->resolveTableName('leave_request');
        $employeeTable = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('lr.employee_id', $scopeEmployeeIds);

        $rows = $this->db->getResults(
            'SELECT lr.id, lr.employee_id, lr.leave_type, lr.start_date, lr.end_date,
                lr.total_days, lr.reason, lr.notes, lr.status, lr.created_at, lr.updated_at,
                lr.created_by, lr.updated_by,
                TRIM(CONCAT(e.first_name, \' \', e.last_name)) AS employee_name,
                e.code AS employee_code,
                e.photo AS employee_photo
             FROM `' . $table . '` AS lr
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = lr.employee_id
             WHERE lr.status = %s' . $scopeSql . '
             ORDER BY lr.created_at DESC, lr.id DESC
             LIMIT %d',
            array_merge([LeaveRequest::STATUS_PENDING], $scopeParams, [$limit]),
        );

        return array_map(
            static fn (array $row): LeaveRequest => LeaveRequest::fromRow($row),
            $rows,
        );
    }

    /**
     * @return list<DashboardDepartmentCard>
     */
    public function departmentOverview(int $limit): array
    {
        $limit = max(1, min(24, $limit));
        $departmentTable = $this->db->resolveTableName('department');
        $employeeTable = $this->db->resolveTableName('employee');

        $rows = $this->db->getResults(
            'SELECT d.id, d.name, d.code, d.status, COUNT(e.id) AS employee_count
             FROM `' . $departmentTable . '` AS d
             LEFT JOIN `' . $employeeTable . '` AS e ON e.department_id = d.id
             GROUP BY d.id, d.name, d.code, d.status
             ORDER BY d.id DESC
             LIMIT %d',
            [$limit],
        );

        return array_map(
            static fn (array $row): DashboardDepartmentCard => DashboardDepartmentCard::fromRow($row),
            $rows,
        );
    }

    /**
     * Managed departments for line managers — team member count excludes dept manager row.
     *
     * @param list<int> $departmentIds
     * @return list<DashboardDepartmentCard>
     */
    public function managedDepartmentOverview(array $departmentIds, int $limit): array
    {
        if ($departmentIds === []) {
            return [];
        }

        $limit = max(1, min(24, $limit));
        $departmentTable = $this->db->resolveTableName('department');
        $employeeTable = $this->db->resolveTableName('employee');
        $placeholders = implode(', ', array_fill(0, count($departmentIds), '%d'));

        $rows = $this->db->getResults(
            'SELECT d.id, d.name, d.code, d.status,
                COUNT(CASE WHEN e.id IS NOT NULL AND e.id != d.manager_id THEN 1 END) AS employee_count
             FROM `' . $departmentTable . '` AS d
             LEFT JOIN `' . $employeeTable . '` AS e ON e.department_id = d.id
             WHERE d.id IN (' . $placeholders . ')
             GROUP BY d.id, d.name, d.code, d.status
             ORDER BY d.id DESC
             LIMIT %d',
            array_merge($departmentIds, [$limit]),
        );

        return array_map(
            static fn (array $row): DashboardDepartmentCard => DashboardDepartmentCard::fromRow($row),
            $rows,
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function countEmployees(?array $scopeEmployeeIds, ?string $employmentStatus = null): int
    {
        $table = $this->db->resolveTableName('employee');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::whereIn('id', $scopeEmployeeIds);
        $clauses = [$scopeSql !== '' ? $scopeSql : '1=1'];
        $params = $scopeParams;

        if ($employmentStatus !== null) {
            $clauses[] = 'employment_status = %s';
            $params[] = $employmentStatus;
        }

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE ' . implode(' AND ', $clauses),
            $params,
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function countAttendanceToday(?array $scopeEmployeeIds, string $today): int
    {
        $table = $this->db->resolveTableName('attendance');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('a.employee_id', $scopeEmployeeIds);

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS a
             WHERE a.attendance_date = %s
             AND a.status IN (%s, %s)' . $scopeSql,
            array_merge(
                [$today, Attendance::STATUS_PRESENT, Attendance::STATUS_LATE],
                $scopeParams,
            ),
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function countOnLeaveToday(?array $scopeEmployeeIds, string $today): int
    {
        $table = $this->db->resolveTableName('leave_request');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('lr.employee_id', $scopeEmployeeIds);

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS lr
             WHERE lr.status = %s
             AND %s BETWEEN lr.start_date AND lr.end_date' . $scopeSql,
            array_merge([LeaveRequest::STATUS_APPROVED, $today], $scopeParams),
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function countPendingLeaves(?array $scopeEmployeeIds): int
    {
        $table = $this->db->resolveTableName('leave_request');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('lr.employee_id', $scopeEmployeeIds);

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS lr
             WHERE lr.status = %s' . $scopeSql,
            array_merge([LeaveRequest::STATUS_PENDING], $scopeParams),
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function countPayrollRecords(?array $scopeEmployeeIds): int
    {
        $table = $this->db->resolveTableName('payroll');
        [$scopeSql, $scopeParams] = ScopeEmployeeIdsSql::andIn('p.employee_id', $scopeEmployeeIds);

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS p WHERE 1=1' . $scopeSql,
            $scopeParams,
        );
    }

    private function countTable(string $logicalTable): int
    {
        $table = $this->db->resolveTableName($logicalTable);

        return (int) $this->db->getVar('SELECT COUNT(*) FROM `' . $table . '`');
    }

    public function employeeStats(int $employeeId, string $today): EmployeeDashboardStats
    {
        $year = (int) gmdate('Y', strtotime($today) ?: time);
        $month = (int) gmdate('n', strtotime($today) ?: time);

        return new EmployeeDashboardStats(
            leaveBalanceDays: $this->leaveBalanceDays($employeeId, $year),
            pendingLeaveRequests: $this->countPendingLeaves([$employeeId]),
            attendanceDaysThisMonth: $this->countAttendanceDaysInMonth($employeeId, $year, $month),
        );
    }

    public function leaveBalanceDays(int $employeeId, int $year): int
    {
        $employeeTable = $this->db->resolveTableName('employee');
        $annual = (int) $this->db->getVar(
            'SELECT annual_leave FROM `' . $employeeTable . '` WHERE id = %d',
            [$employeeId],
        );

        $leaveTable = $this->db->resolveTableName('leave_request');
        $used = (int) $this->db->getVar(
            'SELECT COALESCE(SUM(total_days), 0) FROM `' . $leaveTable . '`
             WHERE employee_id = %d AND status = %s AND leave_type = %s AND YEAR(start_date) = %d',
            [$employeeId, LeaveRequest::STATUS_APPROVED, LeaveRequest::TYPE_ANNUAL, $year],
        );

        return max(0, $annual - $used);
    }

    public function countAttendanceDaysInMonth(int $employeeId, int $year, int $month): int
    {
        $table = $this->db->resolveTableName('attendance');

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '`
             WHERE employee_id = %d
             AND YEAR(attendance_date) = %d
             AND MONTH(attendance_date) = %d
             AND status IN (%s, %s)',
            [$employeeId, $year, $month, Attendance::STATUS_PRESENT, Attendance::STATUS_LATE],
        );
    }

    /**
     * @return list<Payroll>
     */
    public function recentPayslips(int $limit, int $employeeId): array
    {
        $limit = max(1, min(20, $limit));
        $table = $this->db->resolveTableName('payroll');
        $employeeTable = $this->db->resolveTableName('employee');

        $rows = $this->db->getResults(
            'SELECT p.id, p.employee_id, p.payroll_month, p.payroll_year, p.basic_salary, p.allowances,
                p.overtime_amount, p.bonus, p.commission, p.tax, p.insurance, p.advance_deduction,
                p.other_deductions, p.working_days, p.present_days, p.absent_days, p.leave_days,
                p.overtime_hours, p.payment_status, p.payment_date, p.payment_method, p.payment_reference,
                p.notes, p.created_at, p.updated_at, p.created_by, p.updated_by,
                TRIM(CONCAT(e.first_name, \' \', e.last_name)) AS employee_name,
                e.code AS employee_code,
                e.photo AS employee_photo
             FROM `' . $table . '` AS p
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = p.employee_id
             WHERE p.employee_id = %d
             ORDER BY p.id DESC
             LIMIT %d',
            [$employeeId, $limit],
        );

        return array_map(
            static fn (array $row): Payroll => Payroll::fromRow($row),
            $rows,
        );
    }

    /**
     * @return list<LeaveRequest>
     */
    public function upcomingLeaveRequests(int $limit, int $employeeId, string $today): array
    {
        $limit = max(1, min(20, $limit));
        $table = $this->db->resolveTableName('leave_request');
        $employeeTable = $this->db->resolveTableName('employee');

        $rows = $this->db->getResults(
            'SELECT lr.id, lr.employee_id, lr.leave_type, lr.start_date, lr.end_date,
                lr.total_days, lr.reason, lr.notes, lr.status, lr.created_at, lr.updated_at,
                lr.created_by, lr.updated_by,
                TRIM(CONCAT(e.first_name, \' \', e.last_name)) AS employee_name,
                e.code AS employee_code,
                e.photo AS employee_photo
             FROM `' . $table . '` AS lr
             INNER JOIN `' . $employeeTable . '` AS e ON e.id = lr.employee_id
             WHERE lr.employee_id = %d
             AND lr.status = %s
             AND lr.start_date > %s
             ORDER BY lr.start_date ASC, lr.id ASC
             LIMIT %d',
            [$employeeId, LeaveRequest::STATUS_APPROVED, $today, $limit],
        );

        return array_map(
            static fn (array $row): LeaveRequest => LeaveRequest::fromRow($row),
            $rows,
        );
    }

}

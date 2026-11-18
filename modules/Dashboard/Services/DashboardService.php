<?php



declare(strict_types=1);



namespace MF\StaffCore\Modules\Dashboard\Services;



use MF\StaffCore\Authorization\AuthorizationService;

use MF\StaffCore\Modules\Dashboard\DTO\DashboardOverview;

use MF\StaffCore\Modules\Dashboard\DTO\DashboardOverviewStats;

use MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardOverview;

use MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardStats;

use MF\StaffCore\Modules\Dashboard\Repositories\DashboardRepository;



final class DashboardService

{

    private const RECENT_ATTENDANCE_LIMIT = 5;

    private const PENDING_LEAVES_LIMIT = 5;

    private const DEPARTMENT_OVERVIEW_LIMIT = 12;

    private const RECENT_PAYSLIPS_LIMIT = 5;

    private const UPCOMING_LEAVES_LIMIT = 5;



    public function __construct(

        private readonly DashboardRepository $repository,

    ) {

    }



    /**

     * @param list<int>|null $scopeEmployeeIds

     * @param list<int>|null $managedDepartmentIds line manager managed departments

     */

    public function overview(

        ?array $scopeEmployeeIds,

        AuthorizationService $auth,

        ?array $managedDepartmentIds = null,

    ): DashboardOverview {

        $today = gmdate('Y-m-d');

        $stats = $this->resolveStats($scopeEmployeeIds, $managedDepartmentIds, $today, $auth);



        $recentAttendance = $auth->can('attendance.view')

            ? $this->repository->recentAttendance(self::RECENT_ATTENDANCE_LIMIT, $scopeEmployeeIds)

            : [];



        $pendingLeaves = $auth->can('leave_requests.view')

            ? $this->repository->pendingLeaveRequests(self::PENDING_LEAVES_LIMIT, $scopeEmployeeIds)

            : [];



        $departments = [];



        if ($managedDepartmentIds !== null) {

            $departments = $this->repository->managedDepartmentOverview(

                $managedDepartmentIds,

                self::DEPARTMENT_OVERVIEW_LIMIT,

            );

        } elseif ($auth->can('departments.view') && $scopeEmployeeIds === null) {

            $departments = $this->repository->departmentOverview(self::DEPARTMENT_OVERVIEW_LIMIT);

        }



        return new DashboardOverview(

            stats: $stats,

            recentAttendance: $recentAttendance,

            pendingLeaveRequests: $pendingLeaves,

            departments: $departments,

        );

    }



    public function employeeOverview(int $employeeId, AuthorizationService $auth): EmployeeDashboardOverview

    {

        $today = gmdate('Y-m-d');

        $scope = [$employeeId];

        $stats = $this->resolveEmployeeStats($employeeId, $today, $auth);



        $recentAttendance = $auth->can('attendance.view')

            ? $this->repository->recentAttendance(self::RECENT_ATTENDANCE_LIMIT, $scope)

            : [];



        $recentPayslips = $auth->can('my_payslips.view')

            ? $this->repository->recentPayslips(self::RECENT_PAYSLIPS_LIMIT, $employeeId)

            : [];



        $upcomingLeaves = $auth->can('leave_requests.view')

            ? $this->repository->upcomingLeaveRequests(self::UPCOMING_LEAVES_LIMIT, $employeeId, $today)

            : [];
return new EmployeeDashboardOverview(

            stats: $stats,

            recentAttendance: $recentAttendance,

            recentPayslips: $recentPayslips,

            upcomingLeaves: $upcomingLeaves,

        );

    }



    private function resolveEmployeeStats(

        int $employeeId,

        string $today,

        AuthorizationService $auth,

    ): EmployeeDashboardStats {

        $canLeave = $auth->can('leave_requests.view');

        $canAttendance = $auth->can('attendance.view');

        



        if (! $canLeave && ! $canAttendance) {

            return new EmployeeDashboardStats();

        }



        $raw = $this->repository->employeeStats($employeeId, $today);



        return new EmployeeDashboardStats(

            leaveBalanceDays: $canLeave ? $raw->leaveBalanceDays : 0,

            pendingLeaveRequests: $canLeave ? $raw->pendingLeaveRequests : 0,

            attendanceDaysThisMonth: $canAttendance ? $raw->attendanceDaysThisMonth : 0,

        );

    }



    /**

     * @param list<int>|null $scopeEmployeeIds

     * @param list<int>|null $managedDepartmentIds

     */

    private function resolveStats(

        ?array $scopeEmployeeIds,

        ?array $managedDepartmentIds,

        string $today,

        AuthorizationService $auth,

    ): DashboardOverviewStats {

        $isCompanyWide = $scopeEmployeeIds === null && $managedDepartmentIds === null;

        $canEmployees = $auth->can('employees.view');

        $canAttendance = $auth->can('attendance.view');

        $canLeave = $auth->can('leave_requests.view');

        $canDepartments = $auth->can('departments.view')

            && ($isCompanyWide || $managedDepartmentIds !== null);

        $canAssets = $auth->can('assets.view') && $isCompanyWide;

        $canPayroll = $auth->can('payroll.view') && $isCompanyWide;



        if (! $canEmployees && ! $canAttendance && ! $canLeave && ! $canDepartments && ! $canAssets && ! $canPayroll) {

            return new DashboardOverviewStats();

        }



        $raw = $this->repository->stats($scopeEmployeeIds, $today);

        $departmentCount = $managedDepartmentIds !== null

            ? count($managedDepartmentIds)

            : $raw->departments;



        return new DashboardOverviewStats(

            totalEmployees: $canEmployees ? $raw->totalEmployees : 0,

            activeEmployees: $canEmployees ? $raw->activeEmployees : 0,

            attendanceToday: $canAttendance ? $raw->attendanceToday : 0,

            onLeaveToday: $canLeave ? $raw->onLeaveToday : 0,

            departments: $canDepartments ? $departmentCount : 0,

            pendingLeaves: $canLeave ? $raw->pendingLeaves : 0,

            totalAssets: $canAssets ? $raw->totalAssets : 0,

            payrollRecords: $canPayroll ? $raw->payrollRecords : 0,

        );

    }

}



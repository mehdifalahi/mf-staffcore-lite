<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Dashboard\DashboardStatCards;
use MF\StaffCore\Modules\Dashboard\DTO\DashboardDepartmentCard;
use MF\StaffCore\Modules\Dashboard\DTO\DashboardOverview;
use MF\StaffCore\Modules\Dashboard\DTO\DashboardOverviewStats;
use MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardOverview;
use MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardStats;
use MF\StaffCore\Modules\Dashboard\EmployeeDashboardStatCards;
use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Plan\PlanRegistry;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Platforms\WordPress\I18n\UiLabelTranslator;
use MF\StaffCore\UI\PersonAvatar;

final class DashboardPresenter
{
    public function render(): void
    {
        $container = ServiceContainer::instance();
        $auth = $container->authorization();
        $scopeEmployeeIds = null;
        $managedDepartmentIds = null;
        $scopeError = null;

        try {
            $scopeEmployeeIds = $container->scopeEmployeeIdsForModule('dashboard');
            if ($container->isLineManager()) {
                $context = $auth->current();
                $managedDepartmentIds = $context !== null
                    ? $container->managerTeamScopeResolver()->managedDepartmentIds($context->platformUserId)
                    : [];
            }
        } catch (AuthorizationException $exception) {
            $scopeError = $exception->getMessage();
        }

        $urlBuilder = new AdminUrlBuilder();
        $isEmployee = $auth->current()?->role === RoleRegistry::ROLE_EMPLOYEE;

        if ($isEmployee) {
            $employeeId = $scopeError === null && $scopeEmployeeIds !== null && $scopeEmployeeIds !== []
                ? $scopeEmployeeIds[0]
                : null;
            $this->renderEmployeeDashboard($container, $auth, $scopeError, $urlBuilder, $employeeId);

            return;
        }

        $overview = $scopeError === null
            ? $container->dashboard()->overview($scopeEmployeeIds, $auth, $managedDepartmentIds)
            : new DashboardOverview(
                stats: new DashboardOverviewStats(),
                recentAttendance: [],
                pendingLeaveRequests: [],
                departments: [],
            );

        $statCards = DashboardStatCards::build(
            $overview->stats,
            $auth,
            [UiLabelTranslator::class, 'translate'],
        );

        $welcomeName = $this->resolveWelcomeName();
        $roleLabel = $this->resolveRoleLabel($auth);

        $urls = [
            'hr_management' => $urlBuilder->build(new AdminRoute('hr-management', 'employees')),
            'my_profile' => $urlBuilder->build(new AdminRoute('hr-management', 'my-profile')),
            'attendance' => $urlBuilder->build(new AdminRoute('hr-management', 'attendance')),
            'leave_requests' => $urlBuilder->build(new AdminRoute('hr-management', 'leave-requests')),
            'payroll' => $urlBuilder->build(new AdminRoute('hr-management', 'payroll')),
            'departments' => $urlBuilder->build(new AdminRoute('hr-management', 'departments')),
            'employees' => $urlBuilder->build(new AdminRoute('hr-management', 'employees')),
            'assets' => $urlBuilder->build(new AdminRoute('assets', 'assets')),
        ];

        $canHrManagement = $auth->can('employees.view');
        $canMyProfile = $auth->can('my_profile.view');
        $canAttendance = $auth->can('attendance.view');
        $canLeaveRequests = $auth->can('leave_requests.view');
        $canPayroll = $auth->can('payroll.view') && ! $container->isLineManager();
        $canEmployeesCreate = $auth->can('employees.create') && ! $container->isLineManager();
        $canAttendanceCreate = $auth->can('attendance.create');
        $canDepartments = ($auth->can('departments.view') && $scopeEmployeeIds === null && ! $container->isLineManager())
            || ($managedDepartmentIds !== null && $managedDepartmentIds !== []);

        $isEmployee = false;

        include MF_STAFFCORE_PATH . 'modules/Dashboard/Views/index.php';
    }

    private function renderEmployeeDashboard(
        ServiceContainer $container,
        \MF\StaffCore\Authorization\AuthorizationService $auth,
        ?string $scopeError,
        AdminUrlBuilder $urlBuilder,
        ?int $employeeId,
    ): void {
        $employeeOverview = $scopeError === null && $employeeId !== null
            ? $container->dashboard()->employeeOverview($employeeId, $auth)
            : new EmployeeDashboardOverview(
                stats: new EmployeeDashboardStats(),
                recentAttendance: [],
                recentPayslips: [],
                upcomingLeaves: [],
                
            );

        $welcomeName = $this->resolveWelcomeName();
        $roleLabel = $this->resolveRoleLabel($auth);

        $urls = [
            'my_profile' => $urlBuilder->build(new AdminRoute('hr-management', 'my-profile')),
            'my_payslips' => $urlBuilder->build(new AdminRoute('hr-management', 'my-payslips')),
            'attendance' => $urlBuilder->build(new AdminRoute('hr-management', 'attendance')),
            'leave_requests' => $urlBuilder->build(new AdminRoute('hr-management', 'leave-requests')),
            
            
            
        ];

        $statCards = EmployeeDashboardStatCards::build(
            $employeeOverview->stats,
            $auth,
            [UiLabelTranslator::class, 'translate'],
            $urls,
        );

        foreach ($employeeOverview->recentPayslips as $payslip) {
            $urls['payslip_' . $payslip->id] = $urlBuilder->build(
                new AdminRoute('hr-management', 'my-payslips'),
                ['sc_view' => 'payslip', 'payroll_id' => $payslip->id],
            );
        }

        $canLeaveRequests = $auth->can('leave_requests.view');
        $canLeaveCreate = $auth->can('leave_requests.create');
        $canAttendance = $auth->can('attendance.view');
        $canMyPayslips = $auth->can('my_payslips.view');
        
        $canMyProfile = $auth->can('my_profile.view');
        
        

        include MF_STAFFCORE_PATH . 'modules/Dashboard/Views/employee-index.php';
    }

    public static function attendanceEmployeeHtml(Attendance $attendance): string
    {
        $name = trim((string) ($attendance->employeeName ?? ''));
        $code = trim((string) ($attendance->employeeCode ?? ''));

        return PersonAvatar::cellHtml(
            $name !== '' ? $name : '—',
            $attendance->employeePhoto,
            $code !== '' ? $code : null,
        );
    }

    public static function leaveEmployeeHtml(LeaveRequest $leave): string
    {
        $name = trim((string) ($leave->employeeName ?? ''));

        return PersonAvatar::cellHtml(
            $name !== '' ? $name : '—',
            $leave->employeePhoto,
            $leave->leaveType,
        );
    }

    public static function formatAttendanceDate(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(gmdate('M j, Y', $timestamp));
    }

    public static function relativeTime(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return '';
        }

        if (function_exists('human_time_diff')) {
            return sprintf(
                /* translators: %s: human-readable time difference */
                __('%s ago', 'mf-staffcore-lite'),
                human_time_diff($timestamp, (int) current_time('timestamp')),
            );
        }

        return '';
    }

    public static function formatClockTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '—';
        }

        $timestamp = strtotime($time);

        if ($timestamp === false) {
            return esc_html($time);
        }

        return esc_html(gmdate('g:i A', $timestamp));
    }

    public static function formatHours(?string $clockIn, ?string $clockOut): string
    {
        if ($clockIn === null || $clockOut === null || $clockIn === '' || $clockOut === '') {
            return '—';
        }

        $in = strtotime($clockIn);
        $out = strtotime($clockOut);

        if ($in === false || $out === false || $out <= $in) {
            return '—';
        }

        $hours = ($out - $in) / 3600;

        return number_format($hours, 2) . 'h';
    }

    public static function attendanceStatusBadge(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_PRESENT => '<span class="badge bg-success-lt">' . esc_html__('Present', 'mf-staffcore-lite') . '</span>',
            Attendance::STATUS_LATE => '<span class="badge bg-warning-lt">' . esc_html__('Late', 'mf-staffcore-lite') . '</span>',
            Attendance::STATUS_ABSENT => '<span class="badge bg-danger-lt">' . esc_html__('Absent', 'mf-staffcore-lite') . '</span>',
            Attendance::STATUS_ON_LEAVE => '<span class="badge bg-azure-lt">' . esc_html__('On Leave', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($status) . '</span>',
        };
    }

    /**
     * Rating strings mirror PerformanceReview::RATING_* (Pro-only module) as
     * literals so this Lite-shared presenter never depends on that class.
     */
    public static function overallRatingBadge(string $rating): string
    {
        $class = match ($rating) {
            'Outstanding', 'Exceeds Expectations' => 'bg-success-lt',
            'Meets Expectations' => 'bg-azure-lt',
            'Needs Improvement' => 'bg-warning-lt',
            default => 'bg-danger-lt',
        };

        return '<span class="badge ' . esc_attr($class) . '">' . esc_html($rating) . '</span>';
    }

    public static function departmentStatusBadge(string $status): string
    {
        if ($status === Department::STATUS_ACTIVE) {
            return '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>';
        }

        return '<span class="badge bg-secondary-lt">' . esc_html__('Inactive', 'mf-staffcore-lite') . '</span>';
    }

    public static function departmentEmployeeLabel(DashboardDepartmentCard $department): string
    {
        return sprintf(
            /* translators: %d: employee count */
            _n('%d Employee', '%d Employees', $department->employeeCount, 'mf-staffcore-lite'),
            $department->employeeCount,
        );
    }

    private function resolveWelcomeName(): string
    {
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();

            if ($user->display_name !== '') {
                return (string) $user->display_name;
            }

            if ($user->user_login !== '') {
                return (string) $user->user_login;
            }
        }

        return __('User', 'mf-staffcore-lite');
    }

    private function resolveRoleLabel(\MF\StaffCore\Authorization\AuthorizationService $auth): string
    {
        $context = $auth->current();

        if ($context === null) {
            return RoleRegistry::displayLabel(RoleRegistry::ROLE_EMPLOYEE);
        }

        return RoleRegistry::displayLabel($context->role);
    }

    private static function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $letters .= strtoupper($part[0]);

            if (strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '?';
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard\DTO;

use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Modules\Payroll\DTO\Payroll;

final class EmployeeDashboardOverview
{
    /**
     * @param list<Attendance> $recentAttendance
     * @param list<Payroll> $recentPayslips
     * @param list<LeaveRequest> $upcomingLeaves
     */
    public function __construct(
        public readonly EmployeeDashboardStats $stats,
        public readonly array $recentAttendance,
        public readonly array $recentPayslips,
        public readonly array $upcomingLeaves,
    ) {
    }
}

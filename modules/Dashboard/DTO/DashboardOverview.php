<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard\DTO;

use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;

final class DashboardOverview
{
    /**
     * @param list<Attendance> $recentAttendance
     * @param list<LeaveRequest> $pendingLeaveRequests
     * @param list<DashboardDepartmentCard> $departments
     */
    public function __construct(
        public readonly DashboardOverviewStats $stats,
        public readonly array $recentAttendance,
        public readonly array $pendingLeaveRequests,
        public readonly array $departments,
    ) {
    }
}

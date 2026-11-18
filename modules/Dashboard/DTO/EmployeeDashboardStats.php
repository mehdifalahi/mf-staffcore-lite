<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard\DTO;

final class EmployeeDashboardStats
{
    public function __construct(
        public readonly int $leaveBalanceDays = 0,
        public readonly int $pendingLeaveRequests = 0,
        public readonly int $attendanceDaysThisMonth = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard\DTO;

final class DashboardOverviewStats
{
    public function __construct(
        public readonly int $totalEmployees = 0,
        public readonly int $activeEmployees = 0,
        public readonly int $attendanceToday = 0,
        public readonly int $onLeaveToday = 0,
        public readonly int $departments = 0,
        public readonly int $pendingLeaves = 0,
        public readonly int $totalAssets = 0,
        public readonly int $payrollRecords = 0,
    ) {
    }

    public function activeEmployeePercent(): float
    {
        if ($this->totalEmployees <= 0) {
            return 0.0;
        }

        return round(($this->activeEmployees / $this->totalEmployees) * 100, 1);
    }

    public function attendancePresentPercent(): float
    {
        if ($this->totalEmployees <= 0) {
            return 0.0;
        }

        return round(($this->attendanceToday / $this->totalEmployees) * 100, 1);
    }
}

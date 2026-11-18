<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Attendance\DTO;

final class EmployeeAttendanceMonthSummary
{
    public function __construct(
        public readonly int $totalDays = 0,
        public readonly int $presentDays = 0,
        public readonly int $lateDays = 0,
        public readonly float $totalHours = 0.0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyPayslips\DTO;

final class EmployeePayslipSummary
{
    public function __construct(
        public readonly int $totalPayslips = 0,
        public readonly float $thisYearEarnings = 0.0,
        public readonly float $totalPaidEarnings = 0.0,
    ) {
    }
}

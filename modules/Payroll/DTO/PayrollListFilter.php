<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Payroll\DTO;

final class PayrollListFilter
{
    public const QUERY_EMPLOYEE = 'sc_employee';

    public const QUERY_STATUS = 'sc_status';

    public const QUERY_YEAR = 'sc_year';

    public const QUERY_MONTH = 'sc_month';

    public function __construct(
        public readonly ?int $employeeId = null,
        public readonly ?string $paymentStatus = null,
        public readonly ?int $year = null,
        public readonly ?int $month = null,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toQueryArgs(): array
    {
        $args = [];

        if ($this->employeeId !== null) {
            $args[self::QUERY_EMPLOYEE] = $this->employeeId;
        }

        if ($this->paymentStatus !== null) {
            $args[self::QUERY_STATUS] = $this->paymentStatus;
        }

        if ($this->year !== null) {
            $args[self::QUERY_YEAR] = $this->year;
        }

        if ($this->month !== null) {
            $args[self::QUERY_MONTH] = $this->month;
        }

        return $args;
    }
}

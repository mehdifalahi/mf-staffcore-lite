<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\LeaveRequests\DTO;

final class LeaveRequestListFilter
{
    public const QUERY_EMPLOYEE = 'sc_employee';

    public const QUERY_STATUS = 'sc_status';

    public const QUERY_TYPE = 'sc_leave_type';

    public const QUERY_DATE_FROM = 'sc_date_from';

    public const QUERY_DATE_TO = 'sc_date_to';

    public function __construct(
        public readonly ?int $employeeId = null,
        public readonly ?string $status = null,
        public readonly ?string $leaveType = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
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

        if ($this->status !== null) {
            $args[self::QUERY_STATUS] = $this->status;
        }

        if ($this->leaveType !== null) {
            $args[self::QUERY_TYPE] = $this->leaveType;
        }

        if ($this->dateFrom !== null) {
            $args[self::QUERY_DATE_FROM] = $this->dateFrom;
        }

        if ($this->dateTo !== null) {
            $args[self::QUERY_DATE_TO] = $this->dateTo;
        }

        return $args;
    }
}

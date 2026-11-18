<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\DTO;

final class EmployeeListFilter
{
    public const QUERY_DEPARTMENT = 'sc_dept';

    public const QUERY_STATUS = 'sc_status';

    public const QUERY_SEARCH = 'sc_q';

    public function __construct(
        public readonly ?int $departmentId = null,
        public readonly ?string $employmentStatus = null,
        public readonly ?string $search = null,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toQueryArgs(): array
    {
        $args = [];

        if ($this->departmentId !== null) {
            $args[self::QUERY_DEPARTMENT] = $this->departmentId;
        }

        if ($this->employmentStatus !== null) {
            $args[self::QUERY_STATUS] = $this->employmentStatus;
        }

        if ($this->search !== null) {
            $args[self::QUERY_SEARCH] = $this->search;
        }

        return $args;
    }
}

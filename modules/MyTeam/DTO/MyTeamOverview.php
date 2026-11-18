<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyTeam\DTO;

final class MyTeamOverview
{
    /**
     * @param list<MyTeamDepartment> $departments
     */
    public function __construct(
        public readonly int $totalEmployees,
        public readonly int $activeEmployees,
        public readonly int $onLeaveEmployees,
        public readonly array $departments,
    ) {
    }
}

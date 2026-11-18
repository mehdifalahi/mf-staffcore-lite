<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyTeam\Services;

use MF\StaffCore\Modules\MyTeam\DTO\MyTeamOverview;
use MF\StaffCore\Modules\MyTeam\Repositories\MyTeamRepository;
use MF\StaffCore\Support\PaginatedList;

final class MyTeamService
{
    public function __construct(
        private readonly MyTeamRepository $repository,
    ) {
    }

    public function overview(int $managerPlatformUserId): MyTeamOverview
    {
        return new MyTeamOverview(
            totalEmployees: $this->repository->countTeamMembers($managerPlatformUserId),
            activeEmployees: $this->repository->countActiveMembers($managerPlatformUserId),
            onLeaveEmployees: $this->repository->countOnLeaveMembers($managerPlatformUserId),
            departments: $this->repository->findManagedDepartments($managerPlatformUserId),
        );
    }

    public function listPage(
        int $managerPlatformUserId,
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
    ): PaginatedList {
        return $this->repository->findPage($managerPlatformUserId, $page, $perPage);
    }
}

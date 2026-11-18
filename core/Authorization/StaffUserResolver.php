<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Modules\Users\Repositories\StaffUserRepository;

final class StaffUserResolver implements StaffUserResolverInterface
{
    public function __construct(
        private readonly StaffUserRepository $staffUsers,
    ) {
    }

    public function resolveByPlatformUserId(int $platformUserId): ?StaffUserContext
    {
        if ($platformUserId <= 0) {
            return null;
        }

        $staffUser = $this->staffUsers->findByPlatformUserId($platformUserId);

        if ($staffUser === null) {
            return null;
        }

        return StaffUserContext::fromStaffUser($staffUser);
    }
}

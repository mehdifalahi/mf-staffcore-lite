<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

interface StaffUserResolverInterface
{
    public function resolveByPlatformUserId(int $platformUserId): ?StaffUserContext;
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Auth;

/**
 * Resolves the authenticated platform account ID for the current request.
 */
interface CurrentPlatformUserProvider
{
    public function getCurrentPlatformUserId(): int;
}

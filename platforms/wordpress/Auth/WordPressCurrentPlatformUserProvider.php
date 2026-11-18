<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Auth;

use MF\StaffCore\Auth\CurrentPlatformUserProvider;

use function get_current_user_id;

final class WordPressCurrentPlatformUserProvider implements CurrentPlatformUserProvider
{
    public function getCurrentPlatformUserId(): int
    {
        return (int) get_current_user_id();
    }
}

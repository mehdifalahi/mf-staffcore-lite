<?php

declare(strict_types=1);

namespace MF\StaffCore\Auth;

final class PlatformUserInput
{
    public function __construct(
        public readonly string $login,
        public readonly string $email,
        public readonly string $displayName,
        public readonly ?string $password = null,
    ) {
    }
}

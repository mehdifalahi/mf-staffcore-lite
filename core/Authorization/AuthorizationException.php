<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use RuntimeException;

final class AuthorizationException extends RuntimeException
{
    public function __construct(
        string $message = 'Access denied.',
        private readonly ?string $permission = null,
    ) {
        parent::__construct($message);
    }

    public function permission(): ?string
    {
        return $this->permission;
    }
}

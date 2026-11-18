<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

/** Logical admin route (platform adapter turns this into a URL). */
final class AdminRoute
{
    public function __construct(
        public readonly string $menuId,
        public readonly ?string $tabSlug,
    ) {
    }
}

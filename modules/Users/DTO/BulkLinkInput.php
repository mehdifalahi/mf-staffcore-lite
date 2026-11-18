<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

final class BulkLinkInput
{
    /**
     * @param list<int> $platformUserIds
     */
    public function __construct(
        public readonly array $platformUserIds,
        public readonly string $role,
        public readonly string $status,
        public readonly ?string $phone = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

final class BulkLinkOutcome
{
    public function __construct(
        public readonly int $platformUserId,
        public readonly ?string $reason = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform_user_id' => $this->platformUserId,
            'reason' => $this->reason,
        ];
    }
}

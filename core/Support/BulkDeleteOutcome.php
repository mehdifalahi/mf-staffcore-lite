<?php

declare(strict_types=1);

namespace MF\StaffCore\Support;

final class BulkDeleteOutcome
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $reason = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
        ];
    }
}

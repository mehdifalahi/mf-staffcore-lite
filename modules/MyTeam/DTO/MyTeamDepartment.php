<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyTeam\DTO;

final class MyTeamDepartment
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            name: (string) ($row['name'] ?? ''),
            code: (string) ($row['code'] ?? ''),
        );
    }
}

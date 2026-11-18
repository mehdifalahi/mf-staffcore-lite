<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

use MF\StaffCore\Support\PaginatedList;

final class LinkCandidateFilter
{
    public const SOURCE_UNLINKED_ALL = 'unlinked_all';

    public const SOURCE_UNLINKED_WITH_EMPLOYEE = 'unlinked_with_employee';

    public const DEFAULT_SOURCE = self::SOURCE_UNLINKED_WITH_EMPLOYEE;

    public function __construct(
        public readonly string $source = self::DEFAULT_SOURCE,
        public readonly string $search = '',
        public readonly ?int $departmentId = null,
        public readonly ?string $platformRole = null,
        public readonly int $page = 1,
        public readonly int $perPage = PaginatedList::DEFAULT_PER_PAGE,
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $source = (string) ($raw['source'] ?? self::DEFAULT_SOURCE);

        if (! self::isValidSource($source)) {
            $source = self::DEFAULT_SOURCE;
        }

        $page = isset($raw['page']) ? max(1, (int) $raw['page']) : 1;
        $perPage = isset($raw['per_page'])
            ? max(1, min(50, (int) $raw['per_page']))
            : PaginatedList::DEFAULT_PER_PAGE;

        $departmentId = isset($raw['department_id']) && $raw['department_id'] !== ''
            ? (int) $raw['department_id']
            : null;

        if ($departmentId !== null && $departmentId <= 0) {
            $departmentId = null;
        }

        $platformRole = isset($raw['platform_role']) && $raw['platform_role'] !== ''
            ? strtolower(trim((string) $raw['platform_role']))
            : null;

        return new self(
            source: $source,
            search: trim((string) ($raw['search'] ?? '')),
            departmentId: $departmentId,
            platformRole: $platformRole,
            page: $page,
            perPage: $perPage,
        );
    }

    public static function isValidSource(string $source): bool
    {
        return in_array($source, [self::SOURCE_UNLINKED_ALL, self::SOURCE_UNLINKED_WITH_EMPLOYEE], true);
    }
}

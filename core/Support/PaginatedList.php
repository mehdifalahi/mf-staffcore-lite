<?php

declare(strict_types=1);

namespace MF\StaffCore\Support;

/**
 * @template T
 */
final class PaginatedList
{
    public const DEFAULT_PER_PAGE = 10;

    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        if ($this->perPage <= 0) {
            return 1;
        }

        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function offset(): int
    {
        return max(0, ($this->page - 1) * $this->perPage);
    }
}

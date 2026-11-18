<?php

declare(strict_types=1);

namespace MF\StaffCore\Support;

/**
 * Builds a compact page-number sequence for list pagination UIs.
 *
 * @return list<int|'ellipsis'>
 */
final class PaginationWindow
{
    private const SHOW_ALL_THRESHOLD = 7;

    public static function pageItems(int $currentPage, int $totalPages, int $radius = 2): array
    {
        if ($totalPages <= 1) {
            return [];
        }

        if ($totalPages <= self::SHOW_ALL_THRESHOLD) {
            return range(1, $totalPages);
        }

        $items = [1];
        $windowStart = max(2, $currentPage - $radius);
        $windowEnd = min($totalPages - 1, $currentPage + $radius);

        if ($windowStart > 2) {
            $items[] = 'ellipsis';
        } else {
            $windowStart = 2;
        }

        for ($page = $windowStart; $page <= $windowEnd; $page++) {
            $items[] = $page;
        }

        if ($windowEnd < $totalPages - 1) {
            $items[] = 'ellipsis';
        }

        $items[] = $totalPages;

        return $items;
    }
}

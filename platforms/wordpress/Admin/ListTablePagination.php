<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin;

use MF\StaffCore\Navigation\AdminPageResolver;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Support\PaginatedList;

final class ListTablePagination
{
    public const QUERY_PARAM = 'sc_paged';

    public function __construct(
        private readonly AdminUrlBuilder $urlBuilder,
    ) {
    }

    public static function resolvePage(): int
    {
        $page = isset($_GET[self::QUERY_PARAM]) ? (int) $_GET[self::QUERY_PARAM] : 1;

        return $page > 0 ? $page : 1;
    }

    public static function perPage(): int
    {
        return PaginatedList::DEFAULT_PER_PAGE;
    }

    /**
     * @param array<string, scalar> $preserveQuery
     */
    public function pageUrl(int $page, array $preserveQuery = []): string
    {
        $menuId = isset($_GET[AdminPageResolver::QUERY_MENU])
            ? sanitize_key((string) $_GET[AdminPageResolver::QUERY_MENU])
            : 'dashboard';

        $tabSlug = isset($_GET[AdminPageResolver::QUERY_TAB])
            ? sanitize_key((string) $_GET[AdminPageResolver::QUERY_TAB])
            : null;

        if ($tabSlug === '') {
            $tabSlug = null;
        }

        $extra = $preserveQuery;

        if ($page > 1) {
            $extra[self::QUERY_PARAM] = $page;
        }

        return $this->urlBuilder->build(new AdminRoute($menuId, $tabSlug), $extra);
    }

    /**
     * Redirect when the requested page exceeds available pages, or return the last valid page
     * when response headers were already sent (e.g. frontend panel after wp_head).
     *
     * @param array<string, scalar> $preserveQuery
     */
    public function redirectIfPageOutOfRange(int $page, PaginatedList $result, array $preserveQuery = []): int
    {
        if ($result->total <= 0 || $page <= $result->totalPages()) {
            return $page;
        }

        return self::redirectIfPageOutOfRangeToUrl(
            $page,
            $result,
            $this->pageUrl($result->totalPages(), $preserveQuery),
        );
    }

    public static function redirectIfPageOutOfRangeToUrl(int $page, PaginatedList $result, string $redirectUrl): int
    {
        if ($result->total <= 0 || $page <= $result->totalPages()) {
            return $page;
        }

        if (! headers_sent()) {
            wp_safe_redirect($redirectUrl);
            exit;
        }

        return $result->totalPages();
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

/**
 * Portable StaffCore panel deep-link storage (relative admin.php query, no domain).
 *
 * Resolved to full URLs by the platform adapter (WordPress: {@see AdminUrlBuilder}).
 */
final class PanelActionUrl
{
    public const PAGE_QUERY = 'mf-staffcore-lite';

    /**
     * @param array<string, scalar> $extraArgs
     */
    public static function relative(AdminRoute $route, array $extraArgs = []): string
    {
        $args = ['page' => self::PAGE_QUERY];

        if ($route->menuId !== 'dashboard') {
            $args[AdminPageResolver::QUERY_MENU] = $route->menuId;
        }

        if ($route->tabSlug !== null && $route->tabSlug !== '') {
            $args[AdminPageResolver::QUERY_TAB] = $route->tabSlug;
        }

        foreach ($extraArgs as $key => $value) {
            $args[(string) $key] = $value;
        }

        return 'admin.php?' . http_build_query($args);
    }

    /**
     * @return array{route: AdminRoute, extra: array<string, scalar>}|null
     */
    public static function parse(?string $stored): ?array
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        $query = $stored;

        if (str_contains($stored, '?')) {
            if (function_exists('wp_parse_url')) {
                $parsedQuery = wp_parse_url($stored, PHP_URL_QUERY);
            } else {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- non-WordPress runtime fallback
                $parsedQuery = parse_url($stored, PHP_URL_QUERY);
            }

            if (! is_string($parsedQuery) || $parsedQuery === '') {
                return null;
            }

            $query = $parsedQuery;
        }

        parse_str($query, $params);

        if (! is_array($params) || $params === []) {
            return null;
        }

        $menu = isset($params[AdminPageResolver::QUERY_MENU])
            ? (string) $params[AdminPageResolver::QUERY_MENU]
            : 'dashboard';
        $tab = isset($params[AdminPageResolver::QUERY_TAB]) && (string) $params[AdminPageResolver::QUERY_TAB] !== ''
            ? (string) $params[AdminPageResolver::QUERY_TAB]
            : null;

        $reserved = ['page', AdminPageResolver::QUERY_MENU, AdminPageResolver::QUERY_TAB];
        $extra = [];

        foreach ($params as $key => $value) {
            if (in_array($key, $reserved, true) || ! is_scalar($value)) {
                continue;
            }

            $extra[(string) $key] = $value;
        }

        return [
            'route' => new AdminRoute($menu, $tab),
            'extra' => $extra,
        ];
    }
}

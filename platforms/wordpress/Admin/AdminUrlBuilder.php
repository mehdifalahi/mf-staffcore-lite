<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin;

use MF\StaffCore\Navigation\AdminPageResolver;
use MF\StaffCore\Navigation\AdminRoute;

final class AdminUrlBuilder
{
    public const PAGE_SLUG = 'mf-staffcore-lite';
    public const FRONTEND_QUERY = 'mf_staffcore_front';

    public function __construct(
        private readonly ?bool $forceFrontend = null,
    ) {
    }

    /**
     * @param array<string, scalar> $extraArgs
     */
    public function build(AdminRoute $route, array $extraArgs = []): string
    {
        if ($this->isFrontendContext()) {
            return $this->buildFrontend($route, $extraArgs);
        }

        $args = $this->routeQueryArgs($route);

        foreach ($extraArgs as $key => $value) {
            $args[(string) $key] = $value;
        }

        return admin_url('admin.php?' . http_build_query($args));
    }

    /**
     * Query args for GET filter/pagination forms (admin vs frontend panel).
     *
     * @return array<string, scalar>
     */
    public function routeQueryArgs(AdminRoute $route): array
    {
        if ($this->isFrontendContext()) {
            $args = [self::FRONTEND_QUERY => '1'];
        } else {
            $args = ['page' => self::PAGE_SLUG];
        }

        if ($route->menuId !== 'dashboard') {
            $args[AdminPageResolver::QUERY_MENU] = $route->menuId;
        }

        if ($route->tabSlug !== null && $route->tabSlug !== '') {
            $args[AdminPageResolver::QUERY_TAB] = $route->tabSlug;
        }

        return $args;
    }

    /**
     * @param array<string, scalar> $extraArgs
     */
    private function buildFrontend(AdminRoute $route, array $extraArgs = []): string
    {
        $args = $this->routeQueryArgs($route);

        foreach ($extraArgs as $key => $value) {
            $args[(string) $key] = $value;
        }

        return home_url('/?' . http_build_query($args));
    }

    public function isFrontendContext(): bool
    {
        if ($this->forceFrontend !== null) {
            return $this->forceFrontend;
        }

        $flag = isset($_GET[self::FRONTEND_QUERY]) ? sanitize_text_field((string) $_GET[self::FRONTEND_QUERY]) : '';

        return $flag !== '' && $flag !== '0';
    }

    /**
     * Public frontend panel entry (dashboard) for sharing with staff users.
     */
    public static function defaultFrontendPanelUrl(): string
    {
        return (new self(true))->build(new AdminRoute('dashboard', null));
    }
}

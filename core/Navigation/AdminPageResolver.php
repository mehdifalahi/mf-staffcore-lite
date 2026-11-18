<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

/**
 * Resolves menu/tab screen metadata and view paths (platform-agnostic).
 */
final class AdminPageResolver
{
    public const QUERY_MENU = 'sc_menu';
    public const QUERY_TAB = 'sc_tab';

    public function resolve(?string $menuId, ?string $tabSlug): AdminPageContext
    {
        $menuId = $this->normalizeMenuId($menuId);
        $menu = AdminMenuRegistry::findMenu($menuId);

        if ($menu === null) {
            return $this->resolve('dashboard', null);
        }

        $tabs = $menu['tabs'] ?? [];

        if ($tabs !== []) {
            $tabSlug = $this->resolveTabSlug($menu, $tabSlug);
            $tab = AdminMenuRegistry::findTabBySlug($menuId, $tabSlug);

            if ($tab === null) {
                $defaultTab = (string) ($menu['default_tab'] ?? '');

                return $this->resolve($menuId, $defaultTab !== '' ? $defaultTab : null);
            }

            return $this->buildContext($menu, $tab, $tabSlug);
        }

        return $this->buildContext($menu, null, null);
    }

    public function routeForMenu(array $menu): AdminRoute
    {
        $menuId = (string) ($menu['id'] ?? 'dashboard');
        $tabs = $menu['tabs'] ?? [];

        if ($tabs === []) {
            return new AdminRoute($menuId, null);
        }

        $defaultTab = (string) ($menu['default_tab'] ?? '');
        $tabSlugs = array_map(
            static fn (array $tab): string => (string) ($tab['slug'] ?? ''),
            $tabs,
        );

        if ($defaultTab === '' || ! in_array($defaultTab, $tabSlugs, true)) {
            $defaultTab = (string) ($tabs[0]['slug'] ?? '');
        }

        return new AdminRoute($menuId, $defaultTab);
    }

    public function routeForTab(string $menuId, string $tabSlug): AdminRoute
    {
        $slug = strtolower(trim($tabSlug));
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug) ?? '';

        return new AdminRoute($this->normalizeMenuId($menuId), $slug);
    }

    public function viewRelativePath(string $menuId, ?string $tabSlug): string
    {
        if ($tabSlug !== null && $tabSlug !== '') {
            return 'ui/pages/' . $menuId . '/' . $tabSlug . '.php';
        }

        return 'ui/pages/' . $menuId . '/index.php';
    }

    /**
     * @param array<string, mixed> $menu
     * @param array<string, mixed>|null $tab
     */
    private function buildContext(array $menu, ?array $tab, ?string $tabSlug): AdminPageContext
    {
        $menuId = (string) $menu['id'];
        $pageTitle = $tab !== null
            ? (string) $tab['label']
            : (string) $menu['label'];

        $pageDescription = $tab !== null
            ? (string) ($tab['description'] ?? '')
            : (string) ($menu['description'] ?? '');

        return new AdminPageContext(
            menuId: $menuId,
            tabSlug: $tabSlug,
            menu: $menu,
            tab: $tab,
            pageTitle: $pageTitle,
            pageDescription: $pageDescription,
            contentView: $this->viewRelativePath($menuId, $tabSlug),
        );
    }

    private function normalizeMenuId(?string $menuId): string
    {
        $id = strtolower(trim((string) ($menuId ?? '')));
        $id = preg_replace('/[^a-z0-9-]/', '', $id) ?? '';

        return $id !== '' ? $id : 'dashboard';
    }

    /**
     * @param array<string, mixed> $menu
     */
    private function resolveTabSlug(array $menu, ?string $tabSlug): string
    {
        $slug = strtolower(trim((string) ($tabSlug ?? '')));
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug) ?? '';

        if ($slug !== '') {
            return $slug;
        }

        return (string) ($menu['default_tab'] ?? '');
    }
}

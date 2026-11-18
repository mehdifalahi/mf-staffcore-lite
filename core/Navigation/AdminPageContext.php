<?php

declare(strict_types=1);

namespace MF\StaffCore\Navigation;

/**
 * Resolved admin screen (menu + optional tab) for rendering.
 */
final class AdminPageContext
{
    /**
     * @param array<string, mixed> $menu
     * @param array<string, mixed>|null $tab
     */
    public function __construct(
        public readonly string $menuId,
        public readonly ?string $tabSlug,
        public readonly array $menu,
        public readonly ?array $tab,
        public readonly string $pageTitle,
        public readonly string $pageDescription,
        public readonly string $contentView,
    ) {
    }

    public function hasTabs(): bool
    {
        return ($this->menu['tabs'] ?? []) !== [];
    }
}

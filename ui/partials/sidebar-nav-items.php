<?php

declare(strict_types=1);

/**
 * Shared sidebar / mobile nav menu items (same registry data as desktop sidebar).
 *
 * @var list<array<string, mixed>> $menus
 * @var string $activeMenuId
 * @var string|null $activeTabSlug
 * @var \MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder $urlBuilder
 * @var \MF\StaffCore\Navigation\AdminPageResolver $resolver
 * @var string $navListClass Optional extra classes for the ul element
 */

if (! defined('ABSPATH')) {
    exit;
}

$navListClass = $navListClass ?? 'pt-lg-3';
?>
<ul class="navbar-nav <?php echo esc_attr($navListClass); ?>">
    <?php foreach ($menus as $menu) :
        $sidebarTarget = $menu['sidebar_target'] ?? null;
        $targetMenuId = is_array($sidebarTarget)
            ? (string) ($sidebarTarget['menu_id'] ?? '')
            : '';
        $targetTabSlug = is_array($sidebarTarget)
            ? (string) ($sidebarTarget['tab_slug'] ?? '')
            : '';

        if ($targetMenuId !== '' && $targetTabSlug !== '') {
            $isActive = ($activeMenuId ?? '') === $targetMenuId
                && ($activeTabSlug ?? '') === $targetTabSlug;
            $menuUrl = $urlBuilder->build(
                new \MF\StaffCore\Navigation\AdminRoute($targetMenuId, $targetTabSlug),
            );
        } else {
            $isActive = ($menu['id'] ?? '') === ($activeMenuId ?? '');
            $menuUrl = $urlBuilder->build($resolver->routeForMenu($menu));
        }

        $iconName = (string) ($menu['icon'] ?? 'circle');
        ?>
        <li class="nav-item<?php echo $isActive ? ' active' : ''; ?>">
            <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>" href="<?php echo esc_url($menuUrl); ?>">
                <?php \MF\StaffCore\UI\TablerIcon::printNav($iconName); ?>
                <span class="nav-link-title"><?php echo esc_html($menu['label']); ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

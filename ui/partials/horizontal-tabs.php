<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $menuTabs
 * @var string|null $activeTabSlug
 * @var string $activeMenuId
 * @var \MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder $urlBuilder
 * @var \MF\StaffCore\Navigation\AdminPageResolver $resolver
 */

if (! defined('ABSPATH')) {
    exit;
}

if ($menuTabs === []) {
    return;
}
?>
<div class="staffcore-page-tabs mt-3">
    <ul class="nav nav-tabs" role="tablist">
        <?php foreach ($menuTabs as $tab) :
            $slug = (string) ($tab['slug'] ?? '');
            $iconName = (string) ($tab['icon'] ?? 'circle');
            $isActive = $slug === ($activeTabSlug ?? '');
            $tabUrl = $urlBuilder->build($resolver->routeForTab($activeMenuId, $slug));
            ?>
            <li class="nav-item" role="presentation">
                <a
                    class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                    href="<?php echo esc_url($tabUrl); ?>"
                    <?php echo $isActive ? 'aria-current="page"' : ''; ?>
                >
                    <?php \MF\StaffCore\UI\TablerIcon::print($iconName, 'staffcore-tab-icon'); ?>
                    <span><?php echo esc_html($tab['label']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php

declare(strict_types=1);

/**
 * Sidebar / mobile nav Lite badge + upgrade link (Layer A).
 *
 * @var bool $showUpgradeUi
 * @var string $editionLabel
 * @var string $sidebarUpgradeUrl
 */

if (! defined('ABSPATH')) {
    exit;
}

if (empty($showUpgradeUi)) {
    return;
}
?>
<div class="staffcore-sidebar-plan">
    <span class="badge bg-secondary-lt staffcore-edition-badge"><?php echo esc_html($editionLabel); ?></span>
    <?php
    $upgradeUrl = $sidebarUpgradeUrl;
    $linkClass = 'staffcore-sidebar-upgrade-link';
    include MF_STAFFCORE_PATH . 'ui/components/pro-upsell-link.php';
    ?>
</div>

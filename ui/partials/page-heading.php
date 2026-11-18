<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var string $pageDescription
 * @var string $pageHeaderIcon
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-page-heading">
    <div class="staffcore-page-heading-icon" aria-hidden="true">
        <?php \MF\StaffCore\UI\TablerIcon::print($pageHeaderIcon); ?>
    </div>
    <div class="staffcore-page-heading-text">
        <h2 class="page-title mb-0"><?php echo esc_html($pageTitle); ?></h2>
        <?php if (($pageDescription ?? '') !== '') : ?>
            <p class="text-secondary mb-0 mt-1"><?php echo esc_html($pageDescription); ?></p>
        <?php endif; ?>
    </div>
</div>

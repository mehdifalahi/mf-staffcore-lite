<?php

declare(strict_types=1);

/**
 * Settings discovery card — Pro feature summary (Lite only).
 *
 * @var string $upgradeUrl
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="card staffcore-pro-upsell-card mb-3">
    <div class="card-header">
        <h3 class="card-title mb-0"><?php esc_html_e("What's in Pro", 'mf-staffcore-lite'); ?></h3>
    </div>
    <div class="card-body">
        <p class="text-secondary mb-3">
            <?php esc_html_e(
                'Upgrade to unlock the full HR platform — one-time purchase, no license key required.',
                'mf-staffcore-lite',
            ); ?>
        </p>
        <ul class="staffcore-pro-upsell-features mb-4">
            <li><?php esc_html_e('Documents', 'mf-staffcore-lite'); ?></li>
            <li><?php esc_html_e('Awards & recognition', 'mf-staffcore-lite'); ?></li>
            <li><?php esc_html_e('KPIs & performance reviews', 'mf-staffcore-lite'); ?></li>
            <li><?php esc_html_e('Backup & restore', 'mf-staffcore-lite'); ?></li>
            <li><?php esc_html_e('Biometric attendance sync', 'mf-staffcore-lite'); ?></li>
        </ul>
        <?php
        $label = __('Upgrade to Pro ($39)', 'mf-staffcore-lite');
        $asButton = true;
        include MF_STAFFCORE_PATH . 'ui/components/pro-upsell-link.php';
        ?>
    </div>
</div>

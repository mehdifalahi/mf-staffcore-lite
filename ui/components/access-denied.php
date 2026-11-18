<?php

declare(strict_types=1);

/**
 * @var string $accessDeniedMessage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder $urlBuilder
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="card">
    <div class="card-body">
        <div class="alert alert-danger mb-3" role="alert">
            <?php esc_html_e('Access denied', 'mf-staffcore-lite'); ?>
        </div>
        <p class="text-secondary mb-4"><?php echo esc_html($accessDeniedMessage); ?></p>
        <?php if (! isset($urlBuilder) || ! $urlBuilder->isFrontendContext()) : ?>
            <a href="<?php echo esc_url(admin_url()); ?>" class="btn btn-outline-secondary">
                <?php esc_html_e('Back to WordPress Dashboard', 'mf-staffcore-lite'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>

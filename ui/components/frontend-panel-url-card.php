<?php

declare(strict_types=1);

/**
 * @var string $frontendPanelUrl
 */

use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title mb-0"><?php esc_html_e('Frontend panel link', 'mf-staffcore-lite'); ?></h3>
    </div>
    <div class="card-body">
        <p class="text-secondary mb-3">
            <?php esc_html_e(
                'Share this URL with StaffCore users so they can open the HR panel on the public site (without wp-admin). Logged-in users with an active StaffCore account can access the same menus and permissions as in admin.',
                'mf-staffcore-lite',
            ); ?>
        </p>
        <label class="form-label" for="staffcore-frontend-panel-url">
            <?php esc_html_e('Panel URL', 'mf-staffcore-lite'); ?>
        </label>
        <div class="input-group mb-3">
            <input
                type="text"
                class="form-control"
                id="staffcore-frontend-panel-url"
                readonly
                value="<?php echo esc_attr($frontendPanelUrl); ?>"
                data-role="frontend-panel-url"
            >
            <button type="button" class="btn btn-outline btn-secondary" data-action="staffcore-copy-input">
                <?php esc_html_e('Copy URL', 'mf-staffcore-lite'); ?>
            </button>
        </div>
        <p class="text-secondary small mb-0">
            <?php
            TablerIcon::print('info-circle', 'me-1');
            esc_html_e(
                'In WordPress go to Appearance → Menus, add a Custom Link with the panel URL and a label of your choice, then assign the menu to your site header.',
                'mf-staffcore-lite',
            );
            ?>
        </p>
    </div>
</div>

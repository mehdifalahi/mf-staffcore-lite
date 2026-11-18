<?php

declare(strict_types=1);

/**
 * Bulk delete toolbar button (card-header). Requires $canDelete, $bulkDeletePermission.
 */

use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

if (empty($canDelete)) {
    return;
}
?>
<button
    type="button"
    class="btn btn-outline btn-danger"
    data-action="open-bulk-delete"
    data-staffcore-permission="<?php echo esc_attr($bulkDeletePermission); ?>"
    data-role="bulk-delete-btn"
    disabled
>
    <?php TablerIcon::print('trash', 'me-1'); ?>
    <span data-role="bulk-delete-label"><?php esc_html_e('Delete selected (0)', 'mf-staffcore-lite'); ?></span>
</button>

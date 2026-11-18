<?php

declare(strict_types=1);

/**
 * Bulk delete confirm modal. Requires $canDelete, $bulkDeleteModalId, $bulkEntityPlural.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (empty($canDelete)) {
    return;
}

$modalId = $bulkDeleteModalId;
$title = __('Delete selected', 'mf-staffcore-lite');
$size = 'modal-sm';

ob_start();
?>
<p class="mb-0">
    <?php
    printf(
        /* translators: %s: entity type plural e.g. employees */
        esc_html__('Are you sure you want to delete the selected %s?', 'mf-staffcore-lite'),
        esc_html($bulkEntityPlural),
    );
    ?>
    <strong data-role="bulk-delete-count"></strong>
</p>
<p class="text-secondary small mb-0 mt-2">
    <?php esc_html_e('Only rows selected on this page will be deleted.', 'mf-staffcore-lite'); ?>
</p>
<div class="alert alert-danger d-none mt-3 mb-0" data-role="bulk-delete-error" role="alert"></div>
<?php
$bodyHtml = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
<button type="button" class="btn btn-outline btn-danger" data-action="confirm-bulk-delete">
    <span><?php esc_html_e('Delete selected', 'mf-staffcore-lite'); ?></span>
    <span class="spinner-border spinner-border-sm d-none ms-1" data-role="bulk-delete-spinner" aria-hidden="true"></span>
</button>
<?php
$footerHtml = ob_get_clean();

include MF_STAFFCORE_PATH . 'ui/components/modal.php';

<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (empty($canDelete)) {
    return;
}
?>
<th class="w-1">
    <input
        type="checkbox"
        class="form-check-input"
        data-action="select-page"
        aria-label="<?php esc_attr_e('Select all on page', 'mf-staffcore-lite'); ?>"
    >
</th>

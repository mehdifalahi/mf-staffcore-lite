<?php

declare(strict_types=1);

/**
 * @var int|string $rowId
 */

if (! defined('ABSPATH')) {
    exit;
}

if (empty($canDelete)) {
    return;
}
?>
<td>
    <input
        type="checkbox"
        class="form-check-input"
        data-action="select-row"
        data-id="<?php echo esc_attr((string) $rowId); ?>"
    >
</td>

<?php

declare(strict_types=1);

/**
 * Apply + Reset controls for StaffCore list filter GET forms.
 *
 * Icon-only buttons (title + aria-label) so labels are not clipped in narrow filter columns.
 *
 * @var string $filterActionUrl Base list URL (default reset target).
 * @var string|null $filterResetUrl Optional reset URL when non-filter query args must be kept.
 * @var string|null $listFilterActionsColumnClass Bootstrap column class (default col-auto).
 */

use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$filterResetUrl = $filterResetUrl ?? $filterActionUrl;
$listFilterActionsColumnClass = $listFilterActionsColumnClass ?? 'col-auto';
$applyLabel = esc_attr__('Apply Filters', 'mf-staffcore-lite');
$resetLabel = esc_attr__('Reset Filters', 'mf-staffcore-lite');
?>
<div class="<?php echo esc_attr($listFilterActionsColumnClass); ?>">
    <div class="d-flex gap-2">
        <button
            type="submit"
            class="btn btn-secondary btn-icon"
            title="<?php echo esc_attr($applyLabel); ?>"
            aria-label="<?php echo esc_attr($applyLabel); ?>"
        >
            <?php TablerIcon::print('filter'); ?>
        </button>
        <a
            href="<?php echo esc_url($filterResetUrl); ?>"
            class="btn btn-outline btn-secondary btn-icon"
            title="<?php echo esc_attr($resetLabel); ?>"
            aria-label="<?php echo esc_attr($resetLabel); ?>"
        >
            <?php TablerIcon::print('refresh'); ?>
        </a>
    </div>
</div>

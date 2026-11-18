<?php

declare(strict_types=1);

/**
 * Reset link for inline / auto-submit list filter forms.
 *
 * Icon-only (title + aria-label) for consistent sizing in tight filter rows.
 *
 * @var string $filterActionUrl Base list URL (default reset target).
 * @var string|null $filterResetUrl Optional reset URL when non-filter query args must be kept.
 */

use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$filterResetUrl = $filterResetUrl ?? $filterActionUrl;
$resetLabel = esc_attr__('Reset Filters', 'mf-staffcore-lite');
?>
<a
    href="<?php echo esc_url($filterResetUrl); ?>"
    class="btn btn-outline btn-secondary btn-icon staffcore-list-filter-reset"
    title="<?php echo esc_attr($resetLabel); ?>"
    aria-label="<?php echo esc_attr($resetLabel); ?>"
>
    <?php TablerIcon::print('refresh'); ?>
</a>

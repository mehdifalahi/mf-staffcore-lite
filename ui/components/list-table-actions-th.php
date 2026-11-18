<?php

declare(strict_types=1);

/**
 * Actions column header — only when row actions are visible.
 *
 * @var bool $showActionsColumn
 */

if (! defined('ABSPATH')) {
    exit;
}

if (empty($showActionsColumn)) {
    return;
}
?>
<th class="w-1"><?php esc_html_e('Actions', 'mf-staffcore-lite'); ?></th>

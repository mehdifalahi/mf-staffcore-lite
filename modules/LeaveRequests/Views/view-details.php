<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only leave request details (populated via JS).
 */
?>
<div data-role="leave-request-view-details">
    <div class="row g-3">
        <div class="col-md-6" data-role="view-employee-row">
            <div class="text-secondary small mb-1"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-employee">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Leave type', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-leave-type">—</div>
        </div>
        <div class="col-md-4">
            <div class="text-secondary small mb-1"><?php esc_html_e('Start date', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-start-date">—</div>
        </div>
        <div class="col-md-4">
            <div class="text-secondary small mb-1"><?php esc_html_e('End date', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-end-date">—</div>
        </div>
        <div class="col-md-4">
            <div class="text-secondary small mb-1"><?php esc_html_e('Total days', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-total-days">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-status">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Period', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-period" class="text-secondary">—</div>
        </div>
        <div class="col-12">
            <div class="text-secondary small mb-1"><?php esc_html_e('Reason', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-reason" class="text-secondary">—</div>
        </div>
        <div class="col-12">
            <div class="text-secondary small mb-1"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-notes" class="text-secondary">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Created', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-created-at" class="text-secondary">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Updated', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-updated-at" class="text-secondary">—</div>
        </div>
    </div>
</div>

<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only employee details modal body (populated via JS).
 */
?>
<div data-role="employee-view-details">
    <h4 class="text-secondary mb-3"><?php esc_html_e('Personal Information', 'mf-staffcore-lite'); ?></h4>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Employee Code', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-code">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Full Name', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-full-name">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-email">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-phone">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('System User', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-system-user">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-department">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Position', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-position">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Hire Date', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-hire-date">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-status">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Employment Type', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-employment-type">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Birth Date', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-birth-date">—</div>
        </div>
    </div>

    <hr class="my-4">

    <h4 class="text-secondary mb-3"><?php esc_html_e('Address Information', 'mf-staffcore-lite'); ?></h4>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Address', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-address">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('City', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-city">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Province/State', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-province">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Country', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-country">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Zip Code', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-zip-code">—</div>
        </div>
    </div>

    <hr class="my-4">

    <h4 class="text-secondary mb-3"><?php esc_html_e('Salary Information', 'mf-staffcore-lite'); ?></h4>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Basic Salary', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-basic-salary">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Total Allowances', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-total-allowances">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Annual Leave', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-annual-leave">—</div>
        </div>
        <div class="col-md-6">
            <div class="text-secondary small mb-1"><?php esc_html_e('Remaining Leave', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-remaining-leave">—</div>
        </div>
        <div class="col-12">
            <div class="text-secondary small mb-1"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></div>
            <div data-role="view-notes">—</div>
        </div>
    </div>
</div>

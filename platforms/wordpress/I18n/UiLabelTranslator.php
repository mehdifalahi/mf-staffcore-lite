<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\I18n;

/**
 * Translates known UI label literals for WP coding standards
 * (no __($variable) — Plugin Check NonSingularStringLiteralText).
 */
final class UiLabelTranslator
{
    public static function translate(string $text): string
    {
        return match ($text) {
            'Total Employees' => __('Total Employees', 'mf-staffcore-lite'),
            'Active Employees' => __('Active Employees', 'mf-staffcore-lite'),
            'Working Now' => __('Working Now', 'mf-staffcore-lite'),
            'Today Attendance' => __('Today Attendance', 'mf-staffcore-lite'),
            'On Leave Today' => __('On Leave Today', 'mf-staffcore-lite'),
            'Approved' => __('Approved', 'mf-staffcore-lite'),
            'Departments' => __('Departments', 'mf-staffcore-lite'),
            'Pending Leaves' => __('Pending Leaves', 'mf-staffcore-lite'),
            'Total Assets' => __('Total Assets', 'mf-staffcore-lite'),
            'Payroll Records' => __('Payroll Records', 'mf-staffcore-lite'),
            'Total Categories' => __('Total Categories', 'mf-staffcore-lite'),
            'Total Metrics' => __('Total Metrics', 'mf-staffcore-lite'),
            'Total Reviews' => __('Total Reviews', 'mf-staffcore-lite'),
            'Pending Reviews' => __('Pending Reviews', 'mf-staffcore-lite'),
            'Leave Balance' => __('Leave Balance', 'mf-staffcore-lite'),
            'Days Remaining' => __('Days Remaining', 'mf-staffcore-lite'),
            'Pending Requests' => __('Pending Requests', 'mf-staffcore-lite'),
            'Awaiting Approval' => __('Awaiting Approval', 'mf-staffcore-lite'),
            'Attendance' => __('Attendance', 'mf-staffcore-lite'),
            'Days This Month' => __('Days This Month', 'mf-staffcore-lite'),
            'My Documents' => __('My Documents', 'mf-staffcore-lite'),
            'Available' => __('Available', 'mf-staffcore-lite'),
            default => $text,
        };
    }
}

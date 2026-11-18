<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Attendance\DTO\Attendance> $attendanceRecords
 * @var list<array{label: string, value: int|string, icon: string, tone: string, hint?: string, hintTone?: string}> $statCards
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var string|null $scopeError
 * @var int $filterYear
 * @var int $filterMonth
 * @var string $periodLabel
 * @var array<string, scalar> $punchFilterQuery
 * @var string $punchFilterActionUrl
 * @var string $punchFilterResetUrl
 * @var int $punchCurrentPage
 * @var int $punchTotalPages
 * @var int $punchTotalItems
 * @var callable(int): string $punchPageUrl
 */

use MF\StaffCore\Modules\Attendance\DTO\AttendanceListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\AttendancePresenter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\DashboardPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-attendance staffcore-attendance--employee">
    <?php if (! empty($scopeError)) : ?>
        <div class="alert alert-warning mb-3" role="alert"><?php echo esc_html($scopeError); ?></div>
    <?php endif; ?>

    <?php include MF_STAFFCORE_PATH . 'ui/components/stat-cards-outline.php'; ?>

    <div class="card mt-3">
        <div class="card-header staffcore-card-header-with-filters">
            <h3 class="card-title mb-0"><?php esc_html_e('Attendance Records', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions">
                <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="staffcore-inline-filter-form">
                    <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                    <select
                        class="form-select"
                        id="attendance-employee-month"
                        name="<?php echo esc_attr(AttendanceListFilter::QUERY_MONTH); ?>"
                        aria-label="<?php esc_attr_e('Month', 'mf-staffcore-lite'); ?>"
                        data-staffcore-search="off"
                        onchange="this.form.submit()"
                    >
                        <?php AttendancePresenter::monthFilterOptions($filterMonth); ?>
                    </select>
                    <select
                        class="form-select"
                        id="attendance-employee-year"
                        name="<?php echo esc_attr(AttendanceListFilter::QUERY_YEAR); ?>"
                        aria-label="<?php esc_attr_e('Year', 'mf-staffcore-lite'); ?>"
                        data-staffcore-search="off"
                        onchange="this.form.submit()"
                    >
                        <?php AttendancePresenter::yearFilterOptions($filterYear); ?>
                    </select>
                    <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-reset-link.php'; ?>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Clock In', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Clock Out', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Hours', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendanceRecords === []) : ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-secondary mb-2">
                                    <?php TablerIcon::print('box', 'icon-lg'); ?>
                                </div>
                                <div class="fw-medium mb-1"><?php esc_html_e('No attendance records found', 'mf-staffcore-lite'); ?></div>
                                <div class="text-secondary small">
                                    <?php
                                    printf(
                                        /* translators: %s: month and year label, e.g. June 2026 */
                                        esc_html__('No attendance records for %s.', 'mf-staffcore-lite'),
                                        esc_html($periodLabel),
                                    );
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($attendanceRecords as $record) : ?>
                            <tr>
                                <td><?php echo wp_kses_post(DashboardPresenter::formatAttendanceDate($record->attendanceDate)); ?></td>
                                <td>
                                    <span class="badge bg-success-lt">
                                        <?php echo wp_kses_post(DashboardPresenter::formatClockTime($record->clockIn)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-orange-lt">
                                        <?php echo wp_kses_post(DashboardPresenter::formatClockTime($record->clockOut)); ?>
                                    </span>
                                </td>
                                <td class="text-secondary">
                                    <?php echo esc_html(DashboardPresenter::formatHours($record->clockIn, $record->clockOut)); ?>
                                </td>
                                <td><?php echo wp_kses_post(AttendancePresenter::statusBadge($record->status)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $pageUrl = static function (int $page) use ($pagination, $filterQuery): string {
            return $pagination->pageUrl($page, $filterQuery);
        };
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>
</div>

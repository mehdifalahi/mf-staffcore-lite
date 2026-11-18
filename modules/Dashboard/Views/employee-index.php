<?php

declare(strict_types=1);

/**
 * @var \MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardOverview $employeeOverview
 * @var list<array<string, mixed>> $statCards
 * @var string $welcomeName
 * @var string $roleLabel
 * @var array<string, string> $urls
 * @var bool $canLeaveRequests
 * @var bool $canLeaveCreate
 * @var bool $canAttendance
 * @var bool $canMyPayslips
 * @var bool $canMyProfile
 * @var string|null $scopeError
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\DashboardPresenter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PayrollPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-dashboard staffcore-dashboard--employee">
    <?php if (! empty($scopeError)) : ?>
        <div class="alert alert-warning mb-4" role="alert">
            <?php echo esc_html($scopeError); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h2 mb-1"><?php esc_html_e('Dashboard', 'mf-staffcore-lite'); ?></h2>
            <p class="text-secondary mb-0">
                <?php
                printf(
                    /* translators: 1: user display name, 2: role label */
                    esc_html__('Welcome Back, %1$s (%2$s)', 'mf-staffcore-lite'),
                    esc_html($welcomeName),
                    esc_html($roleLabel),
                );
                ?>
            </p>
        </div>
    </div>

    <?php
    $statCardCount = count($statCards);
    $statCardsColumnClass = match ($statCardCount) {
        1 => 'col-12',
        2 => 'col-sm-6',
        3 => 'col-md-4',
        default => 'col-sm-6 col-lg-3',
    };
    include MF_STAFFCORE_PATH . 'ui/components/stat-cards-outline.php';
    ?>

    <?php if ($canLeaveCreate || $canMyPayslips || $canMyProfile) : ?>
        <div class="card mt-3 staffcore-dashboard-quick-actions">
            <div class="card-header">
                <h3 class="card-title mb-0"><?php esc_html_e('Quick Actions', 'mf-staffcore-lite'); ?></h3>
            </div>
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($canLeaveCreate) : ?>
                        <a href="<?php echo esc_url($urls['leave_requests']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('calendar-off', 'me-1'); ?>
                            <?php esc_html_e('Apply for Leave', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canMyPayslips) : ?>
                        <a href="<?php echo esc_url($urls['my_payslips']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('cash', 'me-1'); ?>
                            <?php esc_html_e('View Payslips', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
<?php if ($canMyProfile) : ?>
                        <a href="<?php echo esc_url($urls['my_profile']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('user-circle', 'me-1'); ?>
                            <?php esc_html_e('Edit Profile', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row row-cards mt-2">
        <div class="col-lg-8">
            <?php if ($canAttendance) : ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1"><?php esc_html_e('Recent Attendance', 'mf-staffcore-lite'); ?></h3>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo esc_url($urls['attendance']); ?>" class="btn btn-outline-primary btn-sm">
                                <?php esc_html_e('View All', 'mf-staffcore-lite'); ?>
                                <?php TablerIcon::print('arrow-right', 'ms-1'); ?>
                            </a>
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
                                <?php if ($employeeOverview->recentAttendance === []) : ?>
                                    <tr>
                                        <td colspan="5" class="text-secondary text-center py-4">
                                            <?php esc_html_e('No attendance records yet.', 'mf-staffcore-lite'); ?>
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($employeeOverview->recentAttendance as $attendance) : ?>
                                        <tr>
                                            <td><?php echo wp_kses_post(DashboardPresenter::formatAttendanceDate($attendance->attendanceDate)); ?></td>
                                            <td>
                                                <span class="badge bg-success-lt">
                                                    <?php echo wp_kses_post(DashboardPresenter::formatClockTime($attendance->clockIn)); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-orange-lt">
                                                    <?php echo wp_kses_post(DashboardPresenter::formatClockTime($attendance->clockOut)); ?>
                                                </span>
                                            </td>
                                            <td class="text-secondary">
                                                <?php echo esc_html(DashboardPresenter::formatHours($attendance->clockIn, $attendance->clockOut)); ?>
                                            </td>
                                            <td><?php echo wp_kses_post(DashboardPresenter::attendanceStatusBadge($attendance->status)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($canMyPayslips) : ?>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1"><?php esc_html_e('Recent Payslips', 'mf-staffcore-lite'); ?></h3>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo esc_url($urls['my_payslips']); ?>" class="btn btn-outline-primary btn-sm">
                                <?php esc_html_e('View All', 'mf-staffcore-lite'); ?>
                                <?php TablerIcon::print('arrow-right', 'ms-1'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Month', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Net Salary', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                                    <th class="w-1"><?php esc_html_e('Download', 'mf-staffcore-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($employeeOverview->recentPayslips === []) : ?>
                                    <tr>
                                        <td colspan="4" class="text-secondary text-center py-4">
                                            <?php esc_html_e('No payslips available yet.', 'mf-staffcore-lite'); ?>
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($employeeOverview->recentPayslips as $payslip) : ?>
                                        <tr>
                                            <td><?php echo wp_kses_post(PayrollPresenter::periodLabel($payslip->payrollMonth, $payslip->payrollYear)); ?></td>
                                            <td class="fw-medium"><?php echo wp_kses_post(PayrollPresenter::formatMoney($payslip->netPay())); ?></td>
                                            <td><?php echo wp_kses_post(PayrollPresenter::statusBadge($payslip->paymentStatus)); ?></td>
                                            <td>
                                                <a
                                                    href="<?php echo esc_url($urls['payslip_' . $payslip->id]); ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="<?php esc_attr_e('Download payslip', 'mf-staffcore-lite'); ?>"
                                                >
                                                    <?php TablerIcon::print('download'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if ($canLeaveRequests) : ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-0"><?php esc_html_e('Upcoming Leaves', 'mf-staffcore-lite'); ?></h3>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo esc_url($urls['leave_requests']); ?>" class="btn btn-outline-primary btn-sm">
                                <?php esc_html_e('View All', 'mf-staffcore-lite'); ?>
                                <?php TablerIcon::print('arrow-right', 'ms-1'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($employeeOverview->upcomingLeaves === []) : ?>
                            <div class="text-center py-3">
                                <div class="text-secondary mb-2">
                                    <?php TablerIcon::print('calendar-x', 'icon-lg'); ?>
                                </div>
                                <p class="text-secondary mb-0 small">
                                    <?php esc_html_e('No upcoming leaves', 'mf-staffcore-lite'); ?>
                                </p>
                            </div>
                        <?php else : ?>
                            <div class="list-group list-group-flush staffcore-dashboard-leave-list">
                                <?php foreach ($employeeOverview->upcomingLeaves as $leave) : ?>
                                    <div class="list-group-item px-0">
                                        <div class="fw-medium"><?php echo esc_html($leave->leaveType); ?></div>
                                        <div class="text-secondary small">
                                            <?php
                                            echo esc_html(
                                                DashboardPresenter::formatAttendanceDate($leave->startDate)
                                                . ' – '
                                                . DashboardPresenter::formatAttendanceDate($leave->endDate),
                                            );
                                            ?>
                                        </div>
                                        <div class="text-secondary small">
                                            <?php
                                            $leaveDays = (int) $leave->totalDays;
                                            echo esc_html(
                                                sprintf(
                                                    /* translators: %d: number of leave days */
                                                    _n('%d day', '%d days', $leaveDays, 'mf-staffcore-lite'),
                                                    $leaveDays
                                                )
                                            );
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
</div>
    </div>
</div>

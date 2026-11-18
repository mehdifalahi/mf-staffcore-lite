<?php

declare(strict_types=1);

/**
 * @var \MF\StaffCore\Modules\Dashboard\DTO\DashboardOverview $overview
 * @var list<array<string, mixed>> $statCards
 * @var string $welcomeName
 * @var string $roleLabel
 * @var array<string, string> $urls
 * @var bool $canHrManagement
 * @var bool $canMyProfile
 * @var bool $isEmployee
 * @var bool $canAttendance
 * @var bool $canLeaveRequests
 * @var bool $canPayroll
 * @var bool $canEmployeesCreate
 * @var bool $canAttendanceCreate
 * @var bool $canDepartments
 * @var string|null $scopeError
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\DashboardPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-dashboard">
    <?php if (! empty($scopeError)) : ?>
        <div class="alert alert-warning mb-4" role="alert">
            <?php echo esc_html($scopeError); ?>
        </div>
    <?php endif; ?>
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h2 mb-1"><?php esc_html_e('Dashboard Overview', 'mf-staffcore-lite'); ?></h2>
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
        <div class="btn-list">
            <?php if ($canMyProfile) : ?>
                <a href="<?php echo esc_url($urls['my_profile']); ?>" class="btn btn-outline-primary">
                    <?php TablerIcon::print('user-circle', 'me-1'); ?>
                    <?php esc_html_e('My Profile', 'mf-staffcore-lite'); ?>
                </a>
            <?php endif; ?>
            <?php if ($canHrManagement) : ?>
                <a href="<?php echo esc_url($urls['hr_management']); ?>" class="btn btn-outline-primary">
                    <?php TablerIcon::print('users', 'me-1'); ?>
                    <?php esc_html_e('HR Management', 'mf-staffcore-lite'); ?>
                </a>
            <?php endif; ?>
            <?php if ($canAttendance) : ?>
                <a href="<?php echo esc_url($urls['attendance']); ?>" class="btn btn-outline-primary">
                    <?php TablerIcon::print('clock', 'me-1'); ?>
                    <?php esc_html_e('Attendance', 'mf-staffcore-lite'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php include MF_STAFFCORE_PATH . 'ui/components/stat-cards-outline.php'; ?>

    <?php if ($canMyProfile || $canEmployeesCreate || $canAttendanceCreate || $canLeaveRequests || $canPayroll) : ?>
        <div class="card staffcore-dashboard-quick-actions">
            <div class="card-header">
                <h3 class="card-title mb-0"><?php esc_html_e('Quick Actions', 'mf-staffcore-lite'); ?></h3>
            </div>
            <div class="card-body">
                <div class="staffcore-dashboard-quick-actions__buttons d-flex gap-2">
                    <?php if ($canMyProfile) : ?>
                        <a href="<?php echo esc_url($urls['my_profile']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('user-circle', 'me-1'); ?>
                            <?php esc_html_e('My Profile', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canEmployeesCreate) : ?>
                        <a href="<?php echo esc_url($urls['employees']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('user-plus', 'me-1'); ?>
                            <?php esc_html_e('Add New Employee', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canAttendanceCreate) : ?>
                        <a href="<?php echo esc_url($urls['attendance']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('clock', 'me-1'); ?>
                            <?php esc_html_e('Mark Attendance', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canLeaveRequests) : ?>
                        <a href="<?php echo esc_url($urls['leave_requests']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('calendar-off', 'me-1'); ?>
                            <?php
                            echo $isEmployee
                                ? esc_html__('Request Leave', 'mf-staffcore-lite')
                                : esc_html__('Approve Leaves', 'mf-staffcore-lite');
                            ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canPayroll) : ?>
                        <a href="<?php echo esc_url($urls['payroll']); ?>" class="btn btn-outline-primary">
                            <?php TablerIcon::print('cash', 'me-1'); ?>
                            <?php esc_html_e('Process Payroll', 'mf-staffcore-lite'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canAttendance || $canLeaveRequests) : ?>
    <div class="row row-cards mt-2">
        <?php if ($canAttendance) : ?>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1"><?php esc_html_e('Recent Attendance', 'mf-staffcore-lite'); ?></h3>
                            <p class="text-secondary mb-0 small">
                                <?php esc_html_e('Latest clock-in records', 'mf-staffcore-lite'); ?>
                            </p>
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
                                    <th><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Date', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Clock In', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Clock Out', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Hours', 'mf-staffcore-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($overview->recentAttendance === []) : ?>
                                    <tr>
                                        <td colspan="6" class="text-secondary text-center py-4">
                                            <?php esc_html_e('No attendance records yet.', 'mf-staffcore-lite'); ?>
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($overview->recentAttendance as $attendance) : ?>
                                        <tr>
                                            <td><?php echo wp_kses_post(DashboardPresenter::attendanceEmployeeHtml($attendance)); ?></td>
                                            <td>
                                                <div><?php echo wp_kses_post(DashboardPresenter::formatAttendanceDate($attendance->attendanceDate)); ?></div>
                                                <div class="text-secondary small">
                                                    <?php echo esc_html(DashboardPresenter::relativeTime($attendance->attendanceDate)); ?>
                                                </div>
                                            </td>
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
            </div>
        <?php endif; ?>

        <div class="<?php echo $canAttendance ? 'col-lg-4' : 'col-12'; ?>">
            <?php if ($canLeaveRequests) : ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1"><?php esc_html_e('Pending Leaves', 'mf-staffcore-lite'); ?></h3>
                            <p class="text-secondary mb-0 small">
                                <?php esc_html_e('Requires approval', 'mf-staffcore-lite'); ?>
                            </p>
                        </div>
                        <span class="badge bg-danger"><?php echo esc_html((string) $overview->stats->pendingLeaves); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if ($overview->pendingLeaveRequests === []) : ?>
                            <div class="text-center py-3">
                                <div class="text-success mb-2">
                                    <?php TablerIcon::print('circle-check', 'icon-lg'); ?>
                                </div>
                                <p class="text-secondary mb-0 small">
                                    <?php esc_html_e('No pending leave requests', 'mf-staffcore-lite'); ?>
                                </p>
                            </div>
                        <?php else : ?>
                            <div class="list-group list-group-flush staffcore-dashboard-leave-list">
                                <?php foreach ($overview->pendingLeaveRequests as $leave) : ?>
                                    <div class="list-group-item px-0">
                                        <div class="staffcore-dashboard-leave-list__row">
                                            <div class="staffcore-dashboard-leave-list__main min-w-0">
                                                <?php echo wp_kses_post(DashboardPresenter::leaveEmployeeHtml($leave)); ?>
                                            </div>
                                            <div class="staffcore-dashboard-leave-list__date text-secondary small">
                                                <?php
                                                echo esc_html(
                                                    DashboardPresenter::formatAttendanceDate($leave->startDate)
                                                    . ' – '
                                                    . DashboardPresenter::formatAttendanceDate($leave->endDate),
                                                );
                                                ?>
                                            </div>
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
    <?php endif; ?>

    <?php if ($canDepartments) : ?>
        <div class="card mt-3 staffcore-dashboard-departments">
            <div class="card-header">
                <div>
                    <h3 class="card-title mb-1"><?php esc_html_e('Department Overview', 'mf-staffcore-lite'); ?></h3>
                    <p class="text-secondary mb-0 small">
                        <?php esc_html_e('Employee distribution across departments', 'mf-staffcore-lite'); ?>
                    </p>
                </div>
                <div class="card-actions">
                    <a href="<?php echo esc_url($urls['departments']); ?>" class="btn btn-outline-primary btn-sm">
                        <?php esc_html_e('Manage Departments', 'mf-staffcore-lite'); ?>
                        <?php TablerIcon::print('arrow-right', 'ms-1'); ?>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($overview->departments === []) : ?>
                    <p class="text-secondary mb-0 text-center py-3">
                        <?php esc_html_e('No departments found.', 'mf-staffcore-lite'); ?>
                    </p>
                <?php else : ?>
                    <div class="staffcore-department-grid">
                        <?php foreach ($overview->departments as $department) : ?>
                            <div class="staffcore-department-grid__item">
                                <div class="card staffcore-department-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                            <div>
                                                <div class="h3 mb-0"><?php echo esc_html($department->name); ?></div>
                                                <div class="text-secondary"><?php echo esc_html($department->code); ?></div>
                                            </div>
                                            <?php echo wp_kses_post(DashboardPresenter::departmentStatusBadge($department->status)); ?>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <div class="d-flex align-items-center gap-2 text-secondary staffcore-department-card__meta min-w-0">
                                                <?php TablerIcon::print('users', 'text-azure flex-shrink-0'); ?>
                                                <span class="text-truncate"><?php echo esc_html(DashboardPresenter::departmentEmployeeLabel($department)); ?></span>
                                            </div>
                                            <span class="avatar avatar-sm rounded bg-dark-lt">
                                                <?php TablerIcon::print('building', 'text-azure'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

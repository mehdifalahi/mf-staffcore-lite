<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\MyTeam\DTO\MyTeamMember> $members
 * @var list<\MF\StaffCore\Modules\MyTeam\DTO\MyTeamDepartment> $departments
 * @var array{total: int, active: int, on_leave: int} $stats
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\MyTeamPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-my-team">
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small"><?php esc_html_e('Total Employees', 'mf-staffcore-lite'); ?></div>
                        <div class="h3 mb-0"><?php echo esc_html((string) $stats['total']); ?></div>
                    </div>
                    <span class="avatar bg-azure-lt text-azure">
                        <?php TablerIcon::print('users'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small"><?php esc_html_e('Active', 'mf-staffcore-lite'); ?></div>
                        <div class="h3 mb-0"><?php echo esc_html((string) $stats['active']); ?></div>
                    </div>
                    <span class="avatar bg-success-lt text-success">
                        <?php TablerIcon::print('circle-check'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small"><?php esc_html_e('On Leave', 'mf-staffcore-lite'); ?></div>
                        <div class="h3 mb-0"><?php echo esc_html((string) $stats['on_leave']); ?></div>
                    </div>
                    <span class="avatar bg-warning-lt text-warning">
                        <?php TablerIcon::print('calendar-off'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('My Departments', 'mf-staffcore-lite'); ?></h3>
        </div>
        <div class="card-body">
            <?php if ($departments === []) : ?>
                <p class="text-secondary mb-0"><?php esc_html_e('No managed departments found.', 'mf-staffcore-lite'); ?></p>
            <?php else : ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($departments as $department) : ?>
                        <span class="badge bg-azure-lt text-azure">
                            <?php echo esc_html($department->name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Team Members', 'mf-staffcore-lite'); ?></h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Position', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Joined Date', 'mf-staffcore-lite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($members === []) : ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                <?php esc_html_e('No team members found.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($members as $member) : ?>
                            <tr>
                                <td><?php echo wp_kses_post(MyTeamPresenter::employeeCellHtml($member)); ?></td>
                                <td><?php echo esc_html($member->email); ?></td>
                                <td><span class="staffcore-department-badge"><?php echo esc_html($member->departmentName); ?></span></td>
                                <td><?php echo esc_html($member->positionTitle); ?></td>
                                <td><?php echo wp_kses_post(MyTeamPresenter::statusBadge($member->employmentStatus)); ?></td>
                                <td><?php echo wp_kses_post(MyTeamPresenter::formatHireDate($member->hireDate)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $pageUrl = static function (int $page) use ($pagination): string {
            return $pagination->pageUrl($page);
        };
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>
</div>

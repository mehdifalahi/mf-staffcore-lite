<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Attendance\DTO\Attendance> $attendanceRecords
 * @var list<array{id: int, label: string}> $employees
 * @var string $ajaxUrl
 * @var string $nonce
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var \MF\StaffCore\Modules\Attendance\DTO\AttendanceListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var array<string, scalar> $listPageQuery
 * @var string $filterActionUrl
 * @var string|null $scopeError
 * @var bool $isEmployeeScoped
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 */

use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Attendance\DTO\AttendanceListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\AttendancePresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(7, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-attendance"
    data-staffcore-module="attendance"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if (! empty($isEmployeeScoped) && isset($employees[0])) : ?>
    data-default-employee-id="<?php echo esc_attr((string) $employees[0]['id']); ?>"
    <?php endif; ?>
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <?php if (! empty($scopeError)) : ?>
        <div class="alert alert-warning mb-3" role="alert"><?php echo esc_html($scopeError); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Attendance', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button
                    type="button"
                    class="btn btn-primary shadow-sm"
                    data-action="open-create"
                    data-staffcore-permission="attendance.create"
                >
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add record', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <?php if (empty($isEmployeeScoped)) : ?>
                <div class="col-md-3">
                    <label class="form-label" for="attendance-filter-employee"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="attendance-filter-employee" name="<?php echo esc_attr(AttendanceListFilter::QUERY_EMPLOYEE); ?>" data-staffcore-search="always">
                        <option value=""><?php esc_html_e('All Employees', 'mf-staffcore-lite'); ?></option>
                        <?php AttendancePresenter::employeeOptions($employees, $listFilter->employeeId); ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label" for="attendance-filter-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="attendance-filter-status" name="<?php echo esc_attr(AttendanceListFilter::QUERY_STATUS); ?>">
                        <?php AttendancePresenter::statusFilterOptions($listFilter->status ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="attendance-filter-date-from"><?php esc_html_e('From', 'mf-staffcore-lite'); ?></label>
                    <input type="date" class="form-control" id="attendance-filter-date-from" name="<?php echo esc_attr(AttendanceListFilter::QUERY_DATE_FROM); ?>" value="<?php echo esc_attr($listFilter->dateFrom ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="attendance-filter-date-to"><?php esc_html_e('To', 'mf-staffcore-lite'); ?></label>
                    <input type="date" class="form-control" id="attendance-filter-date-to" name="<?php echo esc_attr(AttendanceListFilter::QUERY_DATE_TO); ?>" value="<?php echo esc_attr($listFilter->dateTo ?? ''); ?>">
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php'; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="attendance-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Date', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Clock in', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Clock out', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Type', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendanceRecords === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No attendance records yet. Add your first record.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($attendanceRecords as $record) :
                            $payload = AttendancePresenter::attendancePayload($record);
                            ?>
                            <tr
                                data-attendance-id="<?php echo esc_attr((string) $record->id); ?>"
                                data-attendance="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $record->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="text-secondary"><?php echo esc_html($record->attendanceDate); ?></td>
                                <td class="fw-medium">
                                    <?php echo wp_kses_post(AttendancePresenter::displayValue($record->employeeName)); ?>
                                    <?php if ($record->employeeCode) : ?>
                                        <span class="text-secondary ms-1">(<?php echo esc_html($record->employeeCode); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary"><?php echo wp_kses_post(AttendancePresenter::displayTime($record->clockIn)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(AttendancePresenter::displayTime($record->clockOut)); ?></td>
                                <td><?php echo wp_kses_post(AttendancePresenter::statusBadge($record->status)); ?></td>
                                <td><?php echo wp_kses_post(AttendancePresenter::typeBadge($record->attendanceType)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-staffcore-permission="attendance.edit"
                                            data-id="<?php echo esc_attr((string) $record->id); ?>"
                                            title="<?php esc_attr_e('Edit', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('edit'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canDelete) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline btn-danger"
                                            data-action="open-delete"
                                            data-staffcore-permission="attendance.delete"
                                            data-id="<?php echo esc_attr((string) $record->id); ?>"
                                            data-label="<?php echo esc_attr(
                                                trim(($record->employeeName ?? '') . ' — ' . $record->attendanceDate)
                                            ); ?>"
                                            title="<?php esc_attr_e('Delete', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('trash'); ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $pageUrl = static function (int $page) use ($pagination, $listPageQuery): string {
            return $pagination->pageUrl($page, $listPageQuery);
        };
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>

    <?php if ($canCreate || $canEdit) :
    ob_start();
    ?>
    <form class="needs-validation" data-role="attendance-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <input type="hidden" name="attendance_type" value="<?php echo esc_attr(Attendance::TYPE_MANUAL); ?>">
        <?php if (! empty($isEmployeeScoped) && isset($employees[0])) : ?>
            <input type="hidden" name="employee_id" value="<?php echo esc_attr((string) $employees[0]['id']); ?>">
        <?php endif; ?>
        <div class="row">
            <?php if (empty($isEmployeeScoped)) : ?>
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="attendance-employee"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></label>
                <select
                    class="form-select w-100"
                    id="attendance-employee"
                    data-staffcore-search="always"
                    name="employee_id"
                    required
                >
                    <option value=""><?php esc_html_e('Select employee', 'mf-staffcore-lite'); ?></option>
                    <?php AttendancePresenter::employeeOptions($employees); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Employee is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <?php endif; ?>
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="attendance-date"><?php esc_html_e('Date', 'mf-staffcore-lite'); ?></label>
                <input
                    type="date"
                    class="form-control"
                    id="attendance-date"
                    name="attendance_date"
                    required
                >
                <div class="invalid-feedback"><?php esc_html_e('Date is required.', 'mf-staffcore-lite'); ?></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="attendance-clock-in"><?php esc_html_e('Clock in', 'mf-staffcore-lite'); ?></label>
                <input type="time" class="form-control" id="attendance-clock-in" name="clock_in">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="attendance-clock-out"><?php esc_html_e('Clock out', 'mf-staffcore-lite'); ?></label>
                <input type="time" class="form-control" id="attendance-clock-out" name="clock_out">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="attendance-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="attendance-status" name="status" required>
                    <?php AttendancePresenter::statusOptions(); ?>
                </select>
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label" for="attendance-remarks"><?php esc_html_e('Remarks', 'mf-staffcore-lite'); ?></label>
            <textarea class="form-control" id="attendance-remarks" name="remarks" rows="3"></textarea>
        </div>
        <div class="alert alert-danger d-none mt-3 mb-0" data-role="form-error" role="alert"></div>
    </form>
    <?php
    $formBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-success" data-action="submit-form">
        <span data-role="submit-label"><?php esc_html_e('Save', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="submit-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $formFooter = ob_get_clean();

    $modalId = 'staffcore-attendance-form-modal';
    $title = __('Attendance record', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this attendance record?', 'mf-staffcore-lite'); ?>
        <strong data-role="delete-label"></strong>
    </p>
    <div class="alert alert-danger d-none mt-3 mb-0" data-role="delete-error" role="alert"></div>
    <?php
    $deleteBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-outline btn-danger" data-action="confirm-delete">
        <span data-role="delete-label"><?php esc_html_e('Delete', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="delete-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $deleteFooter = ob_get_clean();

    $modalId = 'staffcore-attendance-delete-modal';
    $title = __('Delete attendance record', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

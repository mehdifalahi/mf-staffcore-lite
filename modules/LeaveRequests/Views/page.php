<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest> $leaveRequests
 * @var list<array{id: int, label: string}> $employees
 * @var string $ajaxUrl
 * @var string $nonce
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var \MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var string|null $scopeError
 * @var bool $isEmployeeScoped
 * @var bool $canView
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var bool $canApprove
 * @var bool $showRowViewActions
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 */

use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\LeaveRequestsPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete, ! empty($showRowViewActions) || $canApprove);
$tableColspan = ListTableView::colspan(6, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-leave-requests"
    data-staffcore-module="leave-requests"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if (! empty($canView)) : ?>
    data-can-view="1"
    <?php endif; ?>
    <?php if ($canEdit) : ?>
    data-can-edit="1"
    <?php endif; ?>
    <?php if ($canDelete) : ?>
    data-can-delete="1"
    <?php endif; ?>
    <?php if ($canApprove) : ?>
    data-can-approve="1"
    <?php endif; ?>
    <?php if (! empty($isEmployeeScoped)) : ?>
    data-is-employee-scoped="1"
    <?php endif; ?>
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
            <h3 class="card-title"><?php esc_html_e('Leave Requests', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button
                    type="button"
                    class="btn btn-primary shadow-sm"
                    data-action="open-create"
                    data-staffcore-permission="leave_requests.create"
                >
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add request', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <?php if (empty($isEmployeeScoped)) : ?>
                <div class="col-md-3">
                    <label class="form-label" for="leave-filter-employee"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="leave-filter-employee" name="<?php echo esc_attr(LeaveRequestListFilter::QUERY_EMPLOYEE); ?>" data-staffcore-search="always">
                        <option value=""><?php esc_html_e('All Employees', 'mf-staffcore-lite'); ?></option>
                        <?php LeaveRequestsPresenter::employeeOptions($employees, $listFilter->employeeId); ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label" for="leave-filter-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="leave-filter-status" name="<?php echo esc_attr(LeaveRequestListFilter::QUERY_STATUS); ?>">
                        <?php LeaveRequestsPresenter::statusFilterOptions($listFilter->status ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="leave-filter-type"><?php esc_html_e('Type', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="leave-filter-type" name="<?php echo esc_attr(LeaveRequestListFilter::QUERY_TYPE); ?>">
                        <?php LeaveRequestsPresenter::leaveTypeFilterOptions($listFilter->leaveType ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="leave-filter-date-from"><?php esc_html_e('From', 'mf-staffcore-lite'); ?></label>
                    <input type="date" class="form-control" id="leave-filter-date-from" name="<?php echo esc_attr(LeaveRequestListFilter::QUERY_DATE_FROM); ?>" value="<?php echo esc_attr($listFilter->dateFrom ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="leave-filter-date-to"><?php esc_html_e('To', 'mf-staffcore-lite'); ?></label>
                    <input type="date" class="form-control" id="leave-filter-date-to" name="<?php echo esc_attr(LeaveRequestListFilter::QUERY_DATE_TO); ?>" value="<?php echo esc_attr($listFilter->dateTo ?? ''); ?>">
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php'; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="leave-requests-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Type', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Period', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Days', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leaveRequests === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No leave requests yet. Add your first request.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($leaveRequests as $request) :
                            $payload = LeaveRequestsPresenter::leaveRequestPayload($request);
                            ?>
                            <tr
                                data-leave-request-id="<?php echo esc_attr((string) $request->id); ?>"
                                data-leave-request="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $request->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="fw-medium">
                                    <?php echo wp_kses_post(LeaveRequestsPresenter::displayValue($request->employeeName)); ?>
                                    <?php if ($request->employeeCode) : ?>
                                        <span class="text-secondary ms-1">(<?php echo esc_html($request->employeeCode); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($request->leaveType); ?></td>
                                <td class="text-secondary">
                                    <?php echo esc_html($request->startDate . ' — ' . $request->endDate); ?>
                                </td>
                                <td><?php echo esc_html((string) $request->totalDays); ?></td>
                                <td><?php echo wp_kses_post(LeaveRequestsPresenter::statusBadge($request->status)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if (! empty($showRowViewActions)) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-view"
                                            data-staffcore-permission="leave_requests.view"
                                            data-id="<?php echo esc_attr((string) $request->id); ?>"
                                            title="<?php esc_attr_e('View', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('eye'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canEdit && (! $isEmployeeScoped || $request->status === LeaveRequest::STATUS_PENDING)) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-staffcore-permission="leave_requests.edit"
                                            data-id="<?php echo esc_attr((string) $request->id); ?>"
                                            title="<?php esc_attr_e('Edit', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('edit'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canApprove && $request->status === LeaveRequest::STATUS_PENDING) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline btn-success"
                                            data-action="open-approve"
                                            data-staffcore-permission="leave_requests.edit"
                                            data-id="<?php echo esc_attr((string) $request->id); ?>"
                                            data-label="<?php echo esc_attr(
                                                trim(($request->employeeName ?? '') . ' — ' . $request->leaveType . ' (' . $request->startDate . ' — ' . $request->endDate . ')')
                                            ); ?>"
                                            title="<?php esc_attr_e('Approve', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('check'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canDelete) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline btn-danger"
                                            data-action="open-delete"
                                            data-staffcore-permission="leave_requests.delete"
                                            data-id="<?php echo esc_attr((string) $request->id); ?>"
                                            data-label="<?php echo esc_attr(
                                                trim(($request->employeeName ?? '') . ' — ' . $request->leaveType)
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
        $pageUrl = static function (int $page) use ($pagination, $filterQuery): string {
            return $pagination->pageUrl($page, $filterQuery);
        };
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>

    <?php if ($canCreate || $canEdit) :
    ob_start();
    ?>
    <form class="needs-validation" data-role="leave-request-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <?php if (! empty($isEmployeeScoped) && isset($employees[0])) : ?>
            <input type="hidden" name="employee_id" value="<?php echo esc_attr((string) $employees[0]['id']); ?>">
        <?php endif; ?>
        <div class="row">
            <?php if (empty($isEmployeeScoped)) : ?>
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="leave-employee"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></label>
                <select
                    class="form-select w-100"
                    id="leave-employee"
                    data-staffcore-search="always"
                    name="employee_id"
                    required
                >
                    <option value=""><?php esc_html_e('Select employee', 'mf-staffcore-lite'); ?></option>
                    <?php LeaveRequestsPresenter::employeeOptions($employees); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Employee is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <?php endif; ?>
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="leave-type"><?php esc_html_e('Leave type', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="leave-type" name="leave_type" required>
                    <option value=""><?php esc_html_e('Select type', 'mf-staffcore-lite'); ?></option>
                    <?php LeaveRequestsPresenter::leaveTypeOptions(); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Leave type is required.', 'mf-staffcore-lite'); ?></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="leave-start-date"><?php esc_html_e('Start date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="leave-start-date" name="start_date" required>
                <div class="invalid-feedback"><?php esc_html_e('Start date is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="leave-end-date"><?php esc_html_e('End date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="leave-end-date" name="end_date" required>
                <div class="invalid-feedback"><?php esc_html_e('End date is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="leave-total-days"><?php esc_html_e('Total days', 'mf-staffcore-lite'); ?></label>
                <input
                    type="number"
                    class="form-control"
                    id="leave-total-days"
                    name="total_days"
                    min="1"
                    step="1"
                    readonly
                >
                <div class="form-text text-secondary"><?php esc_html_e('Calculated from start and end dates.', 'mf-staffcore-lite'); ?></div>
            </div>
        </div>
        <?php if (! empty($isEmployeeScoped)) : ?>
            <input type="hidden" name="status" value="<?php echo esc_attr(LeaveRequest::STATUS_PENDING); ?>">
        <?php else : ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="leave-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="leave-status" name="status" required>
                    <?php LeaveRequestsPresenter::statusOptions(); ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="leave-reason"><?php esc_html_e('Reason', 'mf-staffcore-lite'); ?></label>
            <textarea class="form-control" id="leave-reason" name="reason" rows="2"></textarea>
        </div>
        <div class="mb-0">
            <label class="form-label" for="leave-notes"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></label>
            <textarea class="form-control" id="leave-notes" name="notes" rows="2"></textarea>
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

    $modalId = 'staffcore-leave-request-form-modal';
    $title = __('Leave request', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this leave request?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-leave-request-delete-modal';
    $title = __('Delete leave request', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canApprove) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to approve this leave request?', 'mf-staffcore-lite'); ?>
        <strong data-role="approve-label"></strong>
    </p>
    <div class="alert alert-danger d-none mt-3 mb-0" data-role="approve-error" role="alert"></div>
    <?php
    $approveBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-success" data-action="confirm-approve">
        <span data-role="approve-submit-label"><?php esc_html_e('Approve', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="approve-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $approveFooter = ob_get_clean();

    $modalId = 'staffcore-leave-request-approve-modal';
    $title = __('Approve leave request', 'mf-staffcore-lite');
    $size = 'modal-sm';
    $bodyHtml = $approveBody;
    $footerHtml = $approveFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if (! empty($showRowViewActions)) :
    ob_start();
    include MF_STAFFCORE_PATH . 'modules/LeaveRequests/Views/view-details.php';
    $viewBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'mf-staffcore-lite'); ?></button>
    <?php
    $viewFooter = ob_get_clean();

    $modalId = 'staffcore-leave-request-view-modal';
    $title = __('Leave request details', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $viewBody;
    $footerHtml = $viewFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

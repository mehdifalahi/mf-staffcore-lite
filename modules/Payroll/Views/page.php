<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Payroll\DTO\Payroll> $payrollRecords
 * @var list<array{id: int, label: string}> $employees
 * @var string $ajaxUrl
 * @var string $nonce
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var \MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var string|null $scopeError
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var bool $showRowViewActions
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 * @var string $payslipBaseUrl
 */

use MF\StaffCore\Modules\Payroll\DTO\Payroll;
use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PayrollPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete, ! empty($showRowViewActions));
$tableColspan = ListTableView::colspan(5, $canDelete, $showActionsColumn);
$currentYear = (int) gmdate('Y');
$currentMonth = (int) gmdate('n');
?>
<div
    class="staffcore-payroll"
    data-staffcore-module="payroll"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-payslip-base-url="<?php echo esc_url($payslipBaseUrl); ?>"
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
            <h3 class="card-title"><?php esc_html_e('Payroll', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-action="open-create">
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add payroll', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <div class="col-md-3">
                    <label class="form-label" for="payroll-filter-employee"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="payroll-filter-employee" name="<?php echo esc_attr(PayrollListFilter::QUERY_EMPLOYEE); ?>" data-staffcore-search="always">
                        <option value=""><?php esc_html_e('All Employees', 'mf-staffcore-lite'); ?></option>
                        <?php PayrollPresenter::employeeOptions($employees, $listFilter->employeeId); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="payroll-filter-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="payroll-filter-status" name="<?php echo esc_attr(PayrollListFilter::QUERY_STATUS); ?>">
                        <?php PayrollPresenter::paymentStatusFilterOptions($listFilter->paymentStatus ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payroll-filter-year"><?php esc_html_e('Year', 'mf-staffcore-lite'); ?></label>
                    <input type="number" class="form-control" id="payroll-filter-year" name="<?php echo esc_attr(PayrollListFilter::QUERY_YEAR); ?>" min="2000" max="2100" value="<?php echo esc_attr((string) ($listFilter->year ?? '')); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payroll-filter-month"><?php esc_html_e('Month', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="payroll-filter-month" name="<?php echo esc_attr(PayrollListFilter::QUERY_MONTH); ?>">
                        <option value=""><?php esc_html_e('All Months', 'mf-staffcore-lite'); ?></option>
                        <?php PayrollPresenter::monthOptions($listFilter->month); ?>
                    </select>
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php'; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="payroll-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Period', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Gross', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Net pay', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Payment', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payrollRecords === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No payroll records yet. Add your first record.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($payrollRecords as $record) :
                            $payload = PayrollPresenter::payrollPayload($record);
                            ?>
                            <tr
                                data-payroll-id="<?php echo esc_attr((string) $record->id); ?>"
                                data-payroll="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $record->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="fw-medium"><?php echo wp_kses_post(PayrollPresenter::periodLabel($record->payrollMonth, $record->payrollYear)); ?></td>
                                <td>
                                    <?php echo wp_kses_post(PayrollPresenter::displayValue($record->employeeName)); ?>
                                    <?php if ($record->employeeCode) : ?>
                                        <span class="text-secondary ms-1">(<?php echo esc_html($record->employeeCode); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary"><?php echo wp_kses_post(PayrollPresenter::formatMoney($record->grossPay())); ?></td>
                                <td class="fw-medium"><?php echo wp_kses_post(PayrollPresenter::formatMoney($record->netPay())); ?></td>
                                <td><?php echo wp_kses_post(PayrollPresenter::statusBadge($record->paymentStatus)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if (! empty($showRowViewActions)) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-payslip"
                                            data-id="<?php echo esc_attr((string) $record->id); ?>"
                                            title="<?php esc_attr_e('Open payslip', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('file-text'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
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
                                            data-id="<?php echo esc_attr((string) $record->id); ?>"
                                            data-label="<?php echo esc_attr(
                                                trim(($record->employeeName ?? '') . ' — ' . $record->payrollMonth . '/' . $record->payrollYear)
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
    <form class="needs-validation" data-role="payroll-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0"><?php esc_html_e('Period & employee', 'mf-staffcore-lite'); ?></h4>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-action="prefill">
                <?php TablerIcon::print('refresh', 'me-1'); ?>
                <?php esc_html_e('Load employee data', 'mf-staffcore-lite'); ?>
            </button>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="payroll-employee"><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="payroll-employee" name="employee_id" data-staffcore-search="always" required>
                    <option value=""><?php esc_html_e('Select employee', 'mf-staffcore-lite'); ?></option>
                    <?php PayrollPresenter::employeeOptions($employees); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Employee is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="payroll-month"><?php esc_html_e('Month', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="payroll-month" name="payroll_month" required>
                    <?php PayrollPresenter::monthOptions(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="payroll-year"><?php esc_html_e('Year', 'mf-staffcore-lite'); ?></label>
                <input
                    type="number"
                    class="form-control"
                    id="payroll-year"
                    name="payroll_year"
                    min="2000"
                    max="2100"
                    value="<?php echo esc_attr((string) $currentYear); ?>"
                    required
                >
            </div>
        </div>

        <h4 class="mt-2 mb-3"><?php esc_html_e('Earnings', 'mf-staffcore-lite'); ?></h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="payroll-basic-salary"><?php esc_html_e('Basic salary', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-basic-salary" name="basic_salary" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="payroll-allowances"><?php esc_html_e('Allowances', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-allowances" name="allowances" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="payroll-overtime-amount"><?php esc_html_e('Overtime amount', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-overtime-amount" name="overtime_amount" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="payroll-bonus"><?php esc_html_e('Bonus', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-bonus" name="bonus" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="payroll-commission"><?php esc_html_e('Commission', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-commission" name="commission" min="0" step="0.01" value="0">
            </div>
        </div>

        <h4 class="mt-2 mb-3"><?php esc_html_e('Deductions', 'mf-staffcore-lite'); ?></h4>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-tax"><?php esc_html_e('Tax', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-tax" name="tax" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-insurance"><?php esc_html_e('Insurance', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-insurance" name="insurance" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-advance"><?php esc_html_e('Advance deduction', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-advance" name="advance_deduction" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-other-deductions"><?php esc_html_e('Other deductions', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-other-deductions" name="other_deductions" min="0" step="0.01" value="0">
            </div>
        </div>

        <h4 class="mt-2 mb-3"><?php esc_html_e('Attendance summary', 'mf-staffcore-lite'); ?></h4>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label required" for="payroll-working-days"><?php esc_html_e('Working days', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-working-days" name="working_days" min="1" step="1" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label required" for="payroll-present-days"><?php esc_html_e('Present days', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-present-days" name="present_days" min="0" step="1" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-absent-days"><?php esc_html_e('Absent days', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-absent-days" name="absent_days" min="0" step="1" value="0">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-leave-days"><?php esc_html_e('Leave days', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-leave-days" name="leave_days" min="0" step="1" value="0">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="payroll-overtime-hours"><?php esc_html_e('Overtime hours', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="payroll-overtime-hours" name="overtime_hours" min="0" step="0.01" value="0">
            </div>
        </div>

        <h4 class="mt-2 mb-3"><?php esc_html_e('Payment', 'mf-staffcore-lite'); ?></h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label required" for="payroll-payment-status"><?php esc_html_e('Payment status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="payroll-payment-status" name="payment_status" required>
                    <?php PayrollPresenter::paymentStatusOptions(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="payroll-payment-date"><?php esc_html_e('Payment date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="payroll-payment-date" name="payment_date">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="payroll-payment-method"><?php esc_html_e('Payment method', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="payroll-payment-method" name="payment_method">
                    <?php PayrollPresenter::paymentMethodOptions(); ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="payroll-payment-reference"><?php esc_html_e('Payment reference', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="payroll-payment-reference" name="payment_reference" maxlength="255">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="payroll-net-preview"><?php esc_html_e('Net pay (preview)', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="payroll-net-preview" readonly data-role="net-preview" value="0.00">
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label" for="payroll-notes"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></label>
            <textarea class="form-control" id="payroll-notes" name="notes" rows="2"></textarea>
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

    $modalId = 'staffcore-payroll-form-modal';
    $title = __('Payroll record', 'mf-staffcore-lite');
    $size = 'modal-xl';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this payroll record?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-payroll-delete-modal';
    $title = __('Delete payroll record', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

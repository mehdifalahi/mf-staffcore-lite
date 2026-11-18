<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Maintenance\DTO\Maintenance> $maintenanceRecords
 * @var list<array{id: int, label: string}> $assets
 * @var list<array{id: int, label: string}> $employees
 * @var string $ajaxUrl
 * @var string $nonce
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var \MF\StaffCore\Modules\Maintenance\DTO\MaintenanceListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 */

use MF\StaffCore\Modules\Maintenance\DTO\Maintenance;
use MF\StaffCore\Modules\Maintenance\DTO\MaintenanceListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\MaintenancePresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(7, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-maintenance"
    data-staffcore-module="maintenance"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Maintenance', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-action="open-create">
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add record', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <div class="col-md-2">
                    <label class="form-label" for="maintenance-filter-asset"><?php esc_html_e('Asset', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="maintenance-filter-asset" name="<?php echo esc_attr(MaintenanceListFilter::QUERY_ASSET); ?>">
                        <option value=""><?php esc_html_e('All Assets', 'mf-staffcore-lite'); ?></option>
                        <?php MaintenancePresenter::assetOptions($assets, $listFilter->assetId); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="maintenance-filter-type"><?php esc_html_e('Type', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="maintenance-filter-type" name="<?php echo esc_attr(MaintenanceListFilter::QUERY_TYPE); ?>">
                        <?php MaintenancePresenter::typeFilterOptions($listFilter->type ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="maintenance-filter-employee"><?php esc_html_e('Performed By', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="maintenance-filter-employee" name="<?php echo esc_attr(MaintenanceListFilter::QUERY_EMPLOYEE); ?>" data-staffcore-search="always">
                        <option value=""><?php esc_html_e('All Employees', 'mf-staffcore-lite'); ?></option>
                        <?php MaintenancePresenter::employeeOptions($employees, $listFilter->performedByEmployeeId); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="maintenance-filter-date-from"><?php esc_html_e('From', 'mf-staffcore-lite'); ?></label>
                    <input type="date" class="form-control" id="maintenance-filter-date-from" name="<?php echo esc_attr(MaintenanceListFilter::QUERY_DATE_FROM); ?>" value="<?php echo esc_attr($listFilter->dateFrom ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="maintenance-filter-date-to"><?php esc_html_e('To', 'mf-staffcore-lite'); ?></label>
                    <input type="date" class="form-control" id="maintenance-filter-date-to" name="<?php echo esc_attr(MaintenanceListFilter::QUERY_DATE_TO); ?>" value="<?php echo esc_attr($listFilter->dateTo ?? ''); ?>">
                </div>
                <?php
                include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php';
                ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="maintenance-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Date', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Asset', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Type', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Cost', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Performed by', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Next service', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($maintenanceRecords === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No maintenance records yet. Add your first record.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($maintenanceRecords as $record) :
                            $payload = MaintenancePresenter::maintenancePayload($record);
                            ?>
                            <tr
                                data-maintenance-id="<?php echo esc_attr((string) $record->id); ?>"
                                data-maintenance="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $record->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="text-secondary"><?php echo esc_html($record->maintenanceDate); ?></td>
                                <td class="fw-medium">
                                    <?php echo wp_kses_post(MaintenancePresenter::displayValue($record->assetName)); ?>
                                    <?php if ($record->assetCode) : ?>
                                        <span class="text-secondary ms-1">(<?php echo esc_html($record->assetCode); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo wp_kses_post(MaintenancePresenter::typeBadge($record->type)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(MaintenancePresenter::formatMoney($record->cost)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(MaintenancePresenter::displayValue($record->performedBy)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(MaintenancePresenter::displayValue($record->nextServiceDate)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
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
                                                trim(($record->assetName ?? '') . ' — ' . $record->maintenanceDate)
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
    <form class="needs-validation" data-role="maintenance-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="maintenance-asset"><?php esc_html_e('Asset', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="maintenance-asset" name="asset_id" required>
                    <option value=""><?php esc_html_e('Select asset', 'mf-staffcore-lite'); ?></option>
                    <?php MaintenancePresenter::assetOptions($assets); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Asset is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="maintenance-date"><?php esc_html_e('Maintenance date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="maintenance-date" name="maintenance_date" required>
                <div class="invalid-feedback"><?php esc_html_e('Maintenance date is required.', 'mf-staffcore-lite'); ?></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required" for="maintenance-type"><?php esc_html_e('Type', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="maintenance-type" name="type" required>
                    <?php MaintenancePresenter::typeOptions(); ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="maintenance-cost"><?php esc_html_e('Cost', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="maintenance-cost" name="cost" min="0" step="0.01">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="maintenance-performed-by"><?php esc_html_e('Performed by', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="maintenance-performed-by" name="performed_by_employee_id">
                    <option value=""><?php esc_html_e('Select employee (optional)', 'mf-staffcore-lite'); ?></option>
                    <?php MaintenancePresenter::employeeOptions($employees); ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="maintenance-next-date"><?php esc_html_e('Next service date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="maintenance-next-date" name="next_service_date">
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label" for="maintenance-notes"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></label>
            <textarea class="form-control" id="maintenance-notes" name="notes" rows="3"></textarea>
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

    $modalId = 'staffcore-maintenance-form-modal';
    $title = __('Maintenance record', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this maintenance record?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-maintenance-delete-modal';
    $title = __('Delete maintenance record', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Assets\DTO\Asset> $assets
 * @var list<\MF\StaffCore\Modules\AssetCategories\DTO\AssetCategory> $categories
 * @var list<\MF\StaffCore\Modules\Suppliers\DTO\Supplier> $suppliers
 * @var list<array{id: int, label: string}> $employees
 * @var string $ajaxUrl
 * @var string $nonce
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var \MF\StaffCore\Modules\Assets\DTO\AssetListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 */

use MF\StaffCore\Modules\Assets\DTO\Asset;
use MF\StaffCore\Modules\Assets\DTO\AssetListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\AssetsPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(7, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-assets"
    data-staffcore-module="assets"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Assets', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-action="open-create">
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add asset', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <div class="col-md-3">
                    <label class="form-label" for="asset-filter-category"><?php esc_html_e('Category', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="asset-filter-category" name="<?php echo esc_attr(AssetListFilter::QUERY_CATEGORY); ?>">
                        <?php AssetsPresenter::categoryFilterOptions($categories, $listFilter->categoryId); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="asset-filter-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="asset-filter-status" name="<?php echo esc_attr(AssetListFilter::QUERY_STATUS); ?>">
                        <?php AssetsPresenter::statusFilterOptions($listFilter->status ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="asset-filter-assigned"><?php esc_html_e('Assigned To', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="asset-filter-assigned" name="<?php echo esc_attr(AssetListFilter::QUERY_ASSIGNED_TO); ?>" data-staffcore-search="always">
                        <option value=""><?php esc_html_e('All Employees', 'mf-staffcore-lite'); ?></option>
                        <?php AssetsPresenter::employeeOptions($employees, $listFilter->assignedToEmployeeId); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="asset-filter-search"><?php esc_html_e('Search', 'mf-staffcore-lite'); ?></label>
                    <input type="search" class="form-control" id="asset-filter-search" name="<?php echo esc_attr(AssetListFilter::QUERY_SEARCH); ?>" value="<?php echo esc_attr($listFilter->search ?? ''); ?>" placeholder="<?php esc_attr_e('Code/Name', 'mf-staffcore-lite'); ?>">
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php'; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="assets-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Asset Code', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Asset Name', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Asset Category', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Asset Status', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Asset Condition', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Asset Value', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Assigned To', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assets === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No assets yet. Add your first asset.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($assets as $asset) :
                            $payload = AssetsPresenter::assetPayload($asset);
                            ?>
                            <tr
                                data-asset-id="<?php echo esc_attr((string) $asset->id); ?>"
                                data-asset="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $asset->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="text-secondary"><?php echo esc_html($asset->code); ?></td>
                                <td><?php echo wp_kses_post(AssetsPresenter::nameCellHtml($asset, $payload)); ?></td>
                                <td><?php echo wp_kses_post(AssetsPresenter::categoryBadge($asset->categoryName)); ?></td>
                                <td><?php echo wp_kses_post(AssetsPresenter::statusBadge($asset->status)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(AssetsPresenter::conditionLabel($asset->assetCondition)); ?></td>
                                <td><?php echo wp_kses_post(AssetsPresenter::formatMoney($asset->currentValue)); ?></td>
                                <td><?php echo wp_kses_post(AssetsPresenter::assignedToLabel($asset->assignedToName)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-id="<?php echo esc_attr((string) $asset->id); ?>"
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
                                            data-id="<?php echo esc_attr((string) $asset->id); ?>"
                                            data-name="<?php echo esc_attr($asset->name); ?>"
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
    <form class="needs-validation" data-role="asset-form" data-staffcore-validate novalidate enctype="multipart/form-data">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="image_current" value="">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label required" for="asset-code"><?php esc_html_e('Asset Code', 'mf-staffcore-lite'); ?></label>
                <input
                    type="text"
                    class="form-control"
                    id="asset-code"
                    name="code"
                    maxlength="100"
                    required
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('e.g., AST-0001', 'mf-staffcore-lite'); ?>"
                >
                <div class="invalid-feedback"><?php esc_html_e('Asset code is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="asset-name"><?php esc_html_e('Asset Name', 'mf-staffcore-lite'); ?></label>
                <input
                    type="text"
                    class="form-control"
                    id="asset-name"
                    name="name"
                    maxlength="255"
                    required
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('Asset name', 'mf-staffcore-lite'); ?>"
                >
                <div class="invalid-feedback"><?php esc_html_e('Asset name is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="asset-category"><?php esc_html_e('Asset Category', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="asset-category" name="category_id" required>
                    <option value=""><?php esc_html_e('Select category', 'mf-staffcore-lite'); ?></option>
                    <?php AssetsPresenter::categoryOptions($categories); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Asset category is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-purchase-date"><?php esc_html_e('Purchase Date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="asset-purchase-date" name="purchase_date">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-purchase-cost"><?php esc_html_e('Purchase Cost', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="asset-purchase-cost" name="purchase_cost" min="0" step="0.01">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-current-value"><?php esc_html_e('Current Value', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="asset-current-value" name="current_value" min="0" step="0.01">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-serial"><?php esc_html_e('Serial Number', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="asset-serial" name="serial_number" maxlength="255" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-warranty"><?php esc_html_e('Warranty Expiry', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="asset-warranty" name="warranty_expiry">
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="asset-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="asset-status" name="status" required>
                    <option value="<?php echo esc_attr(Asset::STATUS_ACTIVE); ?>"><?php esc_html_e('Active', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Asset::STATUS_INACTIVE); ?>"><?php esc_html_e('Inactive', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Asset::STATUS_UNDER_REPAIR); ?>"><?php esc_html_e('Under repair', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Asset::STATUS_DISPOSED); ?>"><?php esc_html_e('Disposed', 'mf-staffcore-lite'); ?></option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label required" for="asset-condition"><?php esc_html_e('Condition', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="asset-condition" name="condition" required>
                    <option value="<?php echo esc_attr(Asset::CONDITION_EXCELLENT); ?>"><?php esc_html_e('Excellent', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Asset::CONDITION_GOOD); ?>"><?php esc_html_e('Good', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Asset::CONDITION_FAIR); ?>"><?php esc_html_e('Fair', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Asset::CONDITION_POOR); ?>"><?php esc_html_e('Poor', 'mf-staffcore-lite'); ?></option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-assigned"><?php esc_html_e('Assigned To', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="asset-assigned" name="assigned_to">
                    <option value=""><?php esc_html_e('Select employee', 'mf-staffcore-lite'); ?></option>
                    <?php AssetsPresenter::employeeOptions($employees); ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-location"><?php esc_html_e('Location', 'mf-staffcore-lite'); ?></label>
                <input
                    type="text"
                    class="form-control"
                    id="asset-location"
                    name="location"
                    maxlength="255"
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('Location', 'mf-staffcore-lite'); ?>"
                >
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-supplier"><?php esc_html_e('Supplier', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="asset-supplier" name="supplier_id">
                    <option value=""><?php esc_html_e('Select supplier', 'mf-staffcore-lite'); ?></option>
                    <?php AssetsPresenter::supplierOptions($suppliers); ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="asset-image"><?php esc_html_e('Asset Image', 'mf-staffcore-lite'); ?></label>
                <input type="file" class="form-control" id="asset-image" name="image" accept="image/*">
                <div class="form-text text-secondary d-none mt-1" data-role="image-current-wrap">
                    <?php esc_html_e('Current:', 'mf-staffcore-lite'); ?>
                    <a href="#" class="staffcore-image-preview-link" data-role="image-current-link"></a>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="asset-notes"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></label>
                <textarea
                    class="form-control"
                    id="asset-notes"
                    name="notes"
                    rows="3"
                    placeholder="<?php esc_attr_e('Notes', 'mf-staffcore-lite'); ?>"
                ></textarea>
            </div>
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

    $modalId = 'staffcore-asset-form-modal';
    $title = __('Asset', 'mf-staffcore-lite');
    $size = 'modal-xl';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this asset?', 'mf-staffcore-lite'); ?>
        <strong data-role="delete-name"></strong>
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

    $modalId = 'staffcore-asset-delete-modal';
    $title = __('Delete asset', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

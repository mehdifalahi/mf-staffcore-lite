<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Suppliers\DTO\Supplier> $suppliers
 * @var string $ajaxUrl
 * @var string $nonce
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 */

use MF\StaffCore\Modules\Suppliers\DTO\Supplier;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\SuppliersPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(5, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-suppliers"
    data-staffcore-module="suppliers"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Suppliers', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-action="open-create">
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add supplier', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="suppliers-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Name', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Contact', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($suppliers === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No suppliers yet. Add your first supplier.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($suppliers as $supplier) : ?>
                            <tr
                                data-supplier-id="<?php echo esc_attr((string) $supplier->id); ?>"
                                data-supplier="<?php echo esc_attr(wp_json_encode($supplier->toArray())); ?>"
                            >
                                <?php
                                $rowId = $supplier->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="fw-medium"><?php echo esc_html($supplier->supplierName); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(SuppliersPresenter::displayValue($supplier->contactPerson)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(SuppliersPresenter::displayValue($supplier->email)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(SuppliersPresenter::displayValue($supplier->phone)); ?></td>
                                <td><?php echo wp_kses_post(SuppliersPresenter::statusBadge($supplier->status)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-id="<?php echo esc_attr((string) $supplier->id); ?>"
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
                                            data-id="<?php echo esc_attr((string) $supplier->id); ?>"
                                            data-name="<?php echo esc_attr($supplier->supplierName); ?>"
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
        $pageUrl = static function (int $page) use ($pagination): string {
            return $pagination->pageUrl($page);
        };
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>

    <?php if ($canCreate || $canEdit) :
    ob_start();
    ?>
    <form class="needs-validation" data-role="supplier-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="supplier-name"><?php esc_html_e('Supplier Name', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-name" name="supplier_name" maxlength="255" required autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('Supplier name is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="supplier-contact"><?php esc_html_e('Contact Person', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-contact" name="contact_person" maxlength="100" autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="supplier-email"><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></label>
                <input type="email" class="form-control" id="supplier-email" name="email" maxlength="255" autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('Enter a valid email address.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="supplier-phone"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-phone" name="phone" maxlength="20" autocomplete="off">
            </div>
            <div class="col-12">
                <label class="form-label" for="supplier-address"><?php esc_html_e('Address', 'mf-staffcore-lite'); ?></label>
                <textarea class="form-control" id="supplier-address" name="address" rows="2"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="supplier-city"><?php esc_html_e('City', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-city" name="city" maxlength="100" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="supplier-state"><?php esc_html_e('State', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-state" name="state" maxlength="100" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="supplier-country"><?php esc_html_e('Country', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-country" name="country" maxlength="100" autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="supplier-postal"><?php esc_html_e('Postal Code', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="supplier-postal" name="postal_code" maxlength="20" autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="supplier-website"><?php esc_html_e('Website', 'mf-staffcore-lite'); ?></label>
                <input type="url" class="form-control" id="supplier-website" name="website" maxlength="255" autocomplete="off" placeholder="https://">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="supplier-payment"><?php esc_html_e('Payment Terms', 'mf-staffcore-lite'); ?></label>
                <input
                    type="text"
                    class="form-control"
                    id="supplier-payment"
                    name="payment_terms"
                    placeholder="<?php esc_attr_e('e.g., Net 30', 'mf-staffcore-lite'); ?>"
                    autocomplete="off"
                >
            </div>
            <div class="col-md-8">
                <label class="form-label" for="supplier-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="supplier-status" name="status" required>
                    <option value="<?php echo esc_attr(Supplier::STATUS_ACTIVE); ?>"><?php esc_html_e('Active', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Supplier::STATUS_INACTIVE); ?>"><?php esc_html_e('Inactive', 'mf-staffcore-lite'); ?></option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="supplier-notes"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></label>
                <textarea class="form-control" id="supplier-notes" name="notes" rows="3"></textarea>
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

    $modalId = 'staffcore-supplier-form-modal';
    $title = __('Supplier', 'mf-staffcore-lite');
    $size = 'modal-xl';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this supplier?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-supplier-delete-modal';
    $title = __('Delete supplier', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

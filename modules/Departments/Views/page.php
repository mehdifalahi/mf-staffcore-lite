<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Departments\DTO\Department> $departments
 * @var list<array{id: int, label: string}> $managers
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

use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\DepartmentsPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(4, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-departments"
    data-staffcore-module="departments"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Departments', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-action="open-create">
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add department', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="departments-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Code', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Department Name', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Manager', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($departments === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No departments yet. Add your first department.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($departments as $department) :
                            $payload = DepartmentsPresenter::departmentPayload($department);
                            ?>
                            <tr
                                data-department-id="<?php echo esc_attr((string) $department->id); ?>"
                                data-department="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $department->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="text-secondary"><?php echo esc_html($department->code); ?></td>
                                <td class="fw-medium"><?php echo esc_html($department->name); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(DepartmentsPresenter::displayValue($department->managerName)); ?></td>
                                <td><?php echo wp_kses_post(DepartmentsPresenter::statusBadge($department->status)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-id="<?php echo esc_attr((string) $department->id); ?>"
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
                                            data-id="<?php echo esc_attr((string) $department->id); ?>"
                                            data-name="<?php echo esc_attr($department->name); ?>"
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
    <form class="needs-validation" data-role="department-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <input type="hidden" name="status" value="<?php echo esc_attr(Department::STATUS_ACTIVE); ?>">
        <div class="mb-3">
            <label class="form-label required" for="department-name"><?php esc_html_e('Department Name', 'mf-staffcore-lite'); ?></label>
            <input
                type="text"
                class="form-control"
                id="department-name"
                name="name"
                maxlength="255"
                required
                autocomplete="off"
            >
            <div class="invalid-feedback"><?php esc_html_e('Department name is required.', 'mf-staffcore-lite'); ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label required" for="department-code"><?php esc_html_e('Code', 'mf-staffcore-lite'); ?></label>
            <input
                type="text"
                class="form-control"
                id="department-code"
                name="code"
                maxlength="50"
                required
                autocomplete="off"
            >
            <div class="invalid-feedback"><?php esc_html_e('Department code is required.', 'mf-staffcore-lite'); ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="department-description"><?php esc_html_e('Description', 'mf-staffcore-lite'); ?></label>
            <textarea class="form-control" id="department-description" name="description" rows="4"></textarea>
        </div>
        <div class="mb-0">
            <label class="form-label" for="department-manager"><?php esc_html_e('Manager', 'mf-staffcore-lite'); ?></label>
            <select class="form-select w-100" id="department-manager" name="manager_id" data-staffcore-search="always">
                <option value=""><?php esc_html_e('Select manager (optional)', 'mf-staffcore-lite'); ?></option>
                <?php DepartmentsPresenter::managerOptions($managers); ?>
            </select>
            <?php if ($managers === []) : ?>
                <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                    <?php esc_html_e('No eligible managers found. Assign the Manager role to a user in Settings → Users.', 'mf-staffcore-lite'); ?>
                </div>
            <?php else : ?>
                <div class="form-text text-secondary d-flex align-items-start gap-1 mt-2">
                    <?php TablerIcon::print('info-circle', 'flex-shrink-0 mt-1'); ?>
                    <span><?php esc_html_e('Lists StaffCore users with the Manager role. If they have no employee profile yet, one is created automatically when you save.', 'mf-staffcore-lite'); ?></span>
                </div>
            <?php endif; ?>
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

    $modalId = 'staffcore-department-form-modal';
    $title = __('Department', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this department?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-department-delete-modal';
    $title = __('Delete department', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

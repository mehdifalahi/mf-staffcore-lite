<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Positions\DTO\Position> $positions
 * @var list<\MF\StaffCore\Modules\Departments\DTO\Department> $departments
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

use MF\StaffCore\Modules\Positions\DTO\Position;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PositionsPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$hasDepartments = $departments !== [];
$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(7, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-positions"
    data-staffcore-module="positions"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <?php if (! $hasDepartments) : ?>
        <div class="alert alert-warning mb-3" role="alert">
            <?php esc_html_e('Add at least one active department before creating positions.', 'mf-staffcore-lite'); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Positions', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button
                    type="button"
                    class="btn btn-primary shadow-sm"
                    data-action="open-create"
                    <?php echo $hasDepartments ? '' : ' disabled'; ?>
                >
                    <?php TablerIcon::print('plus', 'me-1'); ?>
                    <?php esc_html_e('Add position', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="positions-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Code', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Position Title', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Level', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Salary Range', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($positions === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No positions yet. Add your first position.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($positions as $position) :
                            $payload = PositionsPresenter::positionPayload($position);
                            ?>
                            <tr
                                data-position-id="<?php echo esc_attr((string) $position->id); ?>"
                                data-position="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $position->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="text-secondary"><?php echo esc_html($position->code); ?></td>
                                <td class="fw-medium"><?php echo esc_html($position->title); ?></td>
                                <td><?php echo wp_kses_post(PositionsPresenter::displayValue($position->departmentName)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(PositionsPresenter::levelLabel($position->level)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(PositionsPresenter::salaryRange($position->minSalary, $position->maxSalary)); ?></td>
                                <td><?php echo wp_kses_post(PositionsPresenter::statusBadge($position->status)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-id="<?php echo esc_attr((string) $position->id); ?>"
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
                                            data-id="<?php echo esc_attr((string) $position->id); ?>"
                                            data-name="<?php echo esc_attr($position->title); ?>"
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
    <form class="needs-validation" data-role="position-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="position-title"><?php esc_html_e('Position Title', 'mf-staffcore-lite'); ?></label>
                <input
                    type="text"
                    class="form-control"
                    id="position-title"
                    name="title"
                    maxlength="255"
                    required
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('e.g. Software Engineer', 'mf-staffcore-lite'); ?>"
                >
                <div class="invalid-feedback"><?php esc_html_e('Position title is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="position-code"><?php esc_html_e('Code', 'mf-staffcore-lite'); ?></label>
                <input
                    type="text"
                    class="form-control"
                    id="position-code"
                    name="code"
                    maxlength="50"
                    required
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('e.g. SE001', 'mf-staffcore-lite'); ?>"
                >
                <div class="invalid-feedback"><?php esc_html_e('Position code is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="position-department"><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="position-department" name="department_id" required>
                    <option value=""><?php esc_html_e('Select department', 'mf-staffcore-lite'); ?></option>
                    <?php PositionsPresenter::departmentOptions($departments); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Department is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="position-level"><?php esc_html_e('Level', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="position-level" name="level" required>
                    <option value=""><?php esc_html_e('Select level', 'mf-staffcore-lite'); ?></option>
                    <?php PositionsPresenter::levelOptions(); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Level is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="position-min-salary"><?php esc_html_e('Minimum Salary', 'mf-staffcore-lite'); ?></label>
                <input
                    type="number"
                    class="form-control"
                    id="position-min-salary"
                    name="min_salary"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                >
            </div>
            <div class="col-md-6">
                <label class="form-label" for="position-max-salary"><?php esc_html_e('Maximum Salary', 'mf-staffcore-lite'); ?></label>
                <input
                    type="number"
                    class="form-control"
                    id="position-max-salary"
                    name="max_salary"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                >
            </div>
            <div class="col-md-6">
                <label class="form-label" for="position-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="position-status" name="status" required>
                    <option value="<?php echo esc_attr(Position::STATUS_ACTIVE); ?>"><?php esc_html_e('Active', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Position::STATUS_INACTIVE); ?>"><?php esc_html_e('Inactive', 'mf-staffcore-lite'); ?></option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="position-description"><?php esc_html_e('Description', 'mf-staffcore-lite'); ?></label>
                <textarea
                    class="form-control"
                    id="position-description"
                    name="description"
                    rows="3"
                    placeholder="<?php esc_attr_e('Position responsibilities and requirements…', 'mf-staffcore-lite'); ?>"
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

    $modalId = 'staffcore-position-form-modal';
    $title = __('Position', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this position?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-position-delete-modal';
    $title = __('Delete position', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

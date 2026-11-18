<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Employees\DTO\Employee> $employees
 * @var list<\MF\StaffCore\Modules\Departments\DTO\Department> $departments
 * @var list<array{id: int, title: string, department_id: int}> $positionsLookup
 * @var list<array{id: int, label: string}> $userAccounts
 * @var \MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var string $ajaxUrl
 * @var string $nonce
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 * @var bool $showRowViewActions
 * @var string $bulkDeletePermission
 * @var string $bulkDeleteModalId
 * @var string $bulkEntityPlural
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var string|null $scopeError
 */

use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\EmployeesPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete, ! empty($showRowViewActions));
$tableColspan = ListTableView::colspan(9, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-employees"
    data-staffcore-module="employees"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-positions="<?php echo esc_attr(wp_json_encode($positionsLookup)); ?>"
    data-user-accounts="<?php echo esc_attr(wp_json_encode($userAccounts)); ?>"
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
            <h3 class="card-title"><?php esc_html_e('Employees', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions btn-list">
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button
                    type="button"
                    class="btn btn-primary shadow-sm"
                    data-action="open-create"
                    data-staffcore-permission="employees.create"
                >
                    <?php TablerIcon::print('user-plus', 'me-1'); ?>
                    <?php esc_html_e('Add Employee', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <div class="col-md-3">
                    <label class="form-label" for="employee-filter-department"><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="employee-filter-department" name="<?php echo esc_attr(\MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter::QUERY_DEPARTMENT); ?>">
                        <option value=""><?php esc_html_e('All Departments', 'mf-staffcore-lite'); ?></option>
                        <?php EmployeesPresenter::departmentOptions($departments, $listFilter->departmentId); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="employee-filter-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="employee-filter-status" name="<?php echo esc_attr(\MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter::QUERY_STATUS); ?>">
                        <?php EmployeesPresenter::statusFilterOptions($listFilter->employmentStatus ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="employee-filter-search"><?php esc_html_e('Search', 'mf-staffcore-lite'); ?></label>
                    <input
                        type="search"
                        class="form-control"
                        id="employee-filter-search"
                        name="<?php echo esc_attr(\MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter::QUERY_SEARCH); ?>"
                        value="<?php echo esc_attr($listFilter->search ?? ''); ?>"
                        placeholder="<?php esc_attr_e('Search', 'mf-staffcore-lite'); ?>"
                        autocomplete="off"
                    >
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php'; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="employees-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('Employee ID', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Name', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Position', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Annual Leave', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Remaining Leave', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Hire Date', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php esc_html_e('No employees found.', 'mf-staffcore-lite'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($employees as $employee) :
                            $payload = EmployeesPresenter::employeePayload($employee);
                            ?>
                            <tr
                                data-employee-id="<?php echo esc_attr((string) $employee->id); ?>"
                                data-employee="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php
                                $rowId = $employee->id;
                                include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                ?>
                                <td class="text-secondary"><?php echo esc_html($employee->code); ?></td>
                                <td><?php echo wp_kses_post(EmployeesPresenter::nameCellHtml($employee)); ?></td>
                                <td><?php echo wp_kses_post(EmployeesPresenter::departmentBadge($employee->departmentName)); ?></td>
                                <td><?php echo wp_kses_post(EmployeesPresenter::positionCellHtml($employee)); ?></td>
                                <td><?php echo wp_kses_post(EmployeesPresenter::leaveDaysBadge($employee->annualLeave, 'annual')); ?></td>
                                <td><?php echo wp_kses_post(EmployeesPresenter::leaveDaysBadge($employee->remainingLeave, 'remaining')); ?></td>
                                <td><?php echo wp_kses_post(EmployeesPresenter::statusBadge($employee->employmentStatus)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(EmployeesPresenter::formatHireDate($employee->hireDate)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-id="<?php echo esc_attr((string) $employee->id); ?>"
                                            title="<?php esc_attr_e('Edit', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('edit'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (! empty($showRowViewActions)) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            data-action="open-records"
                                            data-id="<?php echo esc_attr((string) $employee->id); ?>"
                                            title="<?php esc_attr_e('Records', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('file-text'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canDelete) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline btn-danger"
                                            data-action="open-delete"
                                            data-id="<?php echo esc_attr((string) $employee->id); ?>"
                                            data-name="<?php echo esc_attr($employee->fullName()); ?>"
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
    <form class="needs-validation" data-role="employee-form" data-staffcore-validate novalidate enctype="multipart/form-data">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="photo_current" value="">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="employee-code"><?php esc_html_e('Employee Code', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-code" name="code" maxlength="50" required autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('Employee code is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="employee-department"><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="employee-department" name="department_id" required>
                    <option value=""><?php esc_html_e('Select Department', 'mf-staffcore-lite'); ?></option>
                    <?php EmployeesPresenter::departmentOptions($departments); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Department is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="employee-position"><?php esc_html_e('Position', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="employee-position" name="position_id" required disabled>
                    <option value=""><?php esc_html_e('Select Position', 'mf-staffcore-lite'); ?></option>
                </select>
                <div class="form-text text-secondary" data-role="position-hint"><?php esc_html_e('Select Department First', 'mf-staffcore-lite'); ?></div>
                <div class="invalid-feedback"><?php esc_html_e('Position is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="employee-first-name"><?php esc_html_e('First Name', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-first-name" name="first_name" maxlength="100" required autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('First name is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="employee-last-name"><?php esc_html_e('Last Name', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-last-name" name="last_name" maxlength="100" required autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('Last name is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="employee-email"><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></label>
                <input type="email" class="form-control" id="employee-email" name="email" maxlength="255" required autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('A valid email is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-phone"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-phone" name="phone" maxlength="20" autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-user"><?php esc_html_e('User Account', 'mf-staffcore-lite'); ?></label>
                <select
                    class="form-select w-100"
                    id="employee-user"
                    name="user_id"
                    data-staffcore-search="always"
                >
                    <option value="">
                        <?php
                        if ($userAccounts === []) {
                            esc_html_e('No User Accounts', 'mf-staffcore-lite');
                        } else {
                            esc_html_e('No linked account', 'mf-staffcore-lite');
                        }
                        ?>
                    </option>
                    <?php EmployeesPresenter::userAccountOptions($userAccounts); ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label required" for="employee-hire-date"><?php esc_html_e('Hire Date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="employee-hire-date" name="hire_date" required>
                <div class="invalid-feedback"><?php esc_html_e('Hire date is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="employee-status"><?php esc_html_e('Employment Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="employee-status" name="employment_status" required>
                    <option value="<?php echo esc_attr(Employee::STATUS_ACTIVE); ?>"><?php esc_html_e('Active', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::STATUS_ON_LEAVE); ?>"><?php esc_html_e('On Leave', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::STATUS_SUSPENDED); ?>"><?php esc_html_e('Suspended', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::STATUS_RESIGNED); ?>"><?php esc_html_e('Resigned', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::STATUS_TERMINATED); ?>"><?php esc_html_e('Terminated', 'mf-staffcore-lite'); ?></option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-type"><?php esc_html_e('Employment Type', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="employee-type" name="employment_type">
                    <option value=""><?php esc_html_e('Select Employment Type', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::TYPE_FULLTIME); ?>"><?php esc_html_e('Full Time', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::TYPE_PARTTIME); ?>"><?php esc_html_e('Part Time', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::TYPE_CONTRACT); ?>"><?php esc_html_e('Contract', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::TYPE_TEMPORARY); ?>"><?php esc_html_e('Temporary', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::TYPE_INTERN); ?>"><?php esc_html_e('Intern', 'mf-staffcore-lite'); ?></option>
                    <option value="<?php echo esc_attr(Employee::TYPE_FREELANCE); ?>"><?php esc_html_e('Freelance', 'mf-staffcore-lite'); ?></option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-birth-date"><?php esc_html_e('Birth Date', 'mf-staffcore-lite'); ?></label>
                <input type="date" class="form-control" id="employee-birth-date" name="birth_date">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-basic-salary"><?php esc_html_e('Basic Salary', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="employee-basic-salary" name="basic_salary" min="0" step="0.01">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-annual-leave"><?php esc_html_e('Annual Leave', 'mf-staffcore-lite'); ?></label>
                <input type="number" class="form-control" id="employee-annual-leave" name="annual_leave" min="0" step="1" value="0">
            </div>
        </div>

        <hr class="my-4">
        <h4 class="mb-3"><?php esc_html_e('Address Information', 'mf-staffcore-lite'); ?></h4>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="employee-address"><?php esc_html_e('Address', 'mf-staffcore-lite'); ?></label>
                <textarea class="form-control" id="employee-address" name="address" rows="2" placeholder="<?php esc_attr_e('Street Address', 'mf-staffcore-lite'); ?>"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="employee-city"><?php esc_html_e('City', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-city" name="city" maxlength="100" placeholder="<?php esc_attr_e('City', 'mf-staffcore-lite'); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="employee-province"><?php esc_html_e('Province/State', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-province" name="province" maxlength="100" placeholder="<?php esc_attr_e('Province/State', 'mf-staffcore-lite'); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="employee-country"><?php esc_html_e('Country', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-country" name="country" maxlength="100" placeholder="<?php esc_attr_e('Country', 'mf-staffcore-lite'); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee-zip"><?php esc_html_e('Zip Code', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="employee-zip" name="zip_code" maxlength="20" autocomplete="off">
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0"><?php esc_html_e('Allowances', 'mf-staffcore-lite'); ?></h4>
            <?php if ($canCreate || $canEdit) : ?>
            <button type="button" class="btn btn-outline-primary btn-sm" data-action="add-allowance">
                <?php TablerIcon::print('plus', 'me-1'); ?>
                <?php esc_html_e('Add Allowance', 'mf-staffcore-lite'); ?>
            </button>
            <?php endif; ?>
        </div>
        <div class="border rounded p-3 mb-3 bg-secondary-lt" data-role="allowances-empty">
            <?php esc_html_e('No allowances added. Click \'Add Allowance\' to add.', 'mf-staffcore-lite'); ?>
        </div>
        <div class="d-none" data-role="allowances-list"></div>

        <hr class="my-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="employee-photo"><?php esc_html_e('Employee Profile Photo', 'mf-staffcore-lite'); ?></label>
                <input type="file" class="form-control" id="employee-photo" name="photo" accept="image/*">
                <div class="form-text text-secondary d-none mt-1" data-role="photo-current-wrap">
                    <?php esc_html_e('Current:', 'mf-staffcore-lite'); ?>
                    <a href="#" class="staffcore-image-preview-link" data-role="photo-current-link"></a>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="employee-notes"><?php esc_html_e('Notes', 'mf-staffcore-lite'); ?></label>
                <textarea class="form-control" id="employee-notes" name="notes" rows="4"></textarea>
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

    $modalId = 'staffcore-employee-form-modal';
    $title = __('Employee', 'mf-staffcore-lite');
    $size = 'modal-xl';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this employee?', 'mf-staffcore-lite'); ?>
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

    $modalId = 'staffcore-employee-delete-modal';
    $title = __('Delete employee', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';

    if (! empty($showRowViewActions)) :
    ob_start();
    include MF_STAFFCORE_PATH . 'modules/Employees/Views/view-details.php';
    $viewBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'mf-staffcore-lite'); ?></button>
    <?php
    $viewFooter = ob_get_clean();

    $modalId = 'staffcore-employee-view-modal';
    $title = __('Employee Details', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $viewBody;
    $footerHtml = $viewFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

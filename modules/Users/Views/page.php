<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Users\DTO\StaffUser> $users
 * @var \MF\StaffCore\Modules\Users\DTO\UserListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
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
 * @var array<int, string> $userAvatarPhotos
 */

use MF\StaffCore\Modules\Users\DTO\LinkCandidateFilter;
use MF\StaffCore\Modules\Users\DTO\StaffUser;
use MF\StaffCore\Modules\Users\DTO\UserListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\UsersPresenter;
use MF\StaffCore\UI\ListTableView;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$showActionsColumn = ListTableView::showActionsColumn($canEdit, $canDelete);
$tableColspan = ListTableView::colspan(7, $canDelete, $showActionsColumn);
?>
<div
    class="staffcore-users"
    data-staffcore-module="users"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    <?php if ($canDelete) : ?>
    data-bulk-delete="1"
    data-bulk-delete-action="<?php echo esc_attr($bulkDeleteAction); ?>"
    <?php endif; ?>
>
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1"><?php esc_html_e('Users Management', 'mf-staffcore-lite'); ?></h3>
                <p class="text-secondary mb-0 small">
                    <?php esc_html_e('Manage system users and their access levels', 'mf-staffcore-lite'); ?>
                </p>
            </div>
            <div class="card-actions btn-list">
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-outline btn-secondary" data-action="open-link" data-staffcore-permission="users.create">
                    <?php TablerIcon::print('link', 'me-1'); ?>
                    <?php esc_html_e('Link existing user', 'mf-staffcore-lite'); ?>
                </button>
                <button type="button" class="btn btn-outline-primary" data-action="open-bulk-link" data-staffcore-permission="users.create">
                    <?php TablerIcon::print('users-group', 'me-1'); ?>
                    <?php esc_html_e('Bulk link', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
                <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-toolbar.php'; ?>
                <?php if ($canCreate) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-action="open-create" data-staffcore-permission="users.create">
                    <?php TablerIcon::print('user-plus', 'me-1'); ?>
                    <?php esc_html_e('Add User', 'mf-staffcore-lite'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="row g-2 align-items-end">
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                <div class="col-md-3">
                    <label class="form-label" for="user-filter-role"><?php esc_html_e('Role', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="user-filter-role" name="<?php echo esc_attr(UserListFilter::QUERY_ROLE); ?>">
                        <?php UsersPresenter::roleFilterOptions($listFilter->role ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="user-filter-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                    <select class="form-select w-100" id="user-filter-status" name="<?php echo esc_attr(UserListFilter::QUERY_STATUS); ?>">
                        <?php UsersPresenter::statusFilterOptions($listFilter->status ?? 'all'); ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="user-filter-search"><?php esc_html_e('Search', 'mf-staffcore-lite'); ?></label>
                    <input
                        type="search"
                        class="form-control"
                        id="user-filter-search"
                        name="<?php echo esc_attr(UserListFilter::QUERY_SEARCH); ?>"
                        value="<?php echo esc_attr($listFilter->search ?? ''); ?>"
                        placeholder="<?php esc_attr_e('Name, username, or email…', 'mf-staffcore-lite'); ?>"
                        autocomplete="off"
                    >
                </div>
                <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-actions.php'; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table" data-role="users-table">
                <thead>
                    <tr>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-th.php'; ?>
                        <th><?php esc_html_e('User', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Role', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Login Attempts', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Created', 'mf-staffcore-lite'); ?></th>
                        <?php include MF_STAFFCORE_PATH . 'ui/components/list-table-actions-th.php'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users === []) : ?>
                        <tr data-role="empty-row">
                            <td colspan="<?php echo (int) $tableColspan; ?>" class="text-secondary text-center py-4">
                                <?php if ($listFilter->hasActiveCriteria()) : ?>
                                    <?php esc_html_e('No users found.', 'mf-staffcore-lite'); ?>
                                <?php else : ?>
                                    <?php esc_html_e('No users yet. Add your first StaffCore user.', 'mf-staffcore-lite'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($users as $user) :
                            $payload = UsersPresenter::userPayload($user);
                            ?>
                            <tr
                                data-user-id="<?php echo esc_attr((string) $user->id); ?>"
                                data-user="<?php echo esc_attr(wp_json_encode($payload)); ?>"
                            >
                                <?php if ($canDelete && ! $user->isProtected()) :
                                    $rowId = $user->id;
                                    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-checkbox-td.php';
                                elseif ($canDelete) : ?>
                                <td></td>
                                <?php endif; ?>
                                <td><?php echo wp_kses_post(UsersPresenter::userCellHtml($user, $userAvatarPhotos[$user->id] ?? null)); ?></td>
                                <td><?php echo wp_kses_post(UsersPresenter::roleBadge($user->role)); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <?php echo wp_kses_post(UsersPresenter::loginAttemptsBadge($user)); ?>
                                        <?php if ($canEdit && $user->failedLoginAttempts > 0) : ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-icon btn-outline-secondary"
                                                data-action="reset-login-attempts"
                                                data-id="<?php echo esc_attr((string) $user->id); ?>"
                                                title="<?php esc_attr_e('Reset login attempts', 'mf-staffcore-lite'); ?>"
                                            >
                                                <?php TablerIcon::print('refresh'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-secondary"><?php echo wp_kses_post(UsersPresenter::displayValue($user->phone)); ?></td>
                                <td><?php echo wp_kses_post(UsersPresenter::statusBadge($user->status)); ?></td>
                                <td class="text-secondary"><?php echo wp_kses_post(UsersPresenter::formatDate($user->createdAt)); ?></td>
                                <?php if ($showActionsColumn) : ?>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($canEdit) : ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-edit"
                                            data-id="<?php echo esc_attr((string) $user->id); ?>"
                                            title="<?php esc_attr_e('Edit', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('edit'); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-action="open-password"
                                            data-id="<?php echo esc_attr((string) $user->id); ?>"
                                            data-label="<?php echo esc_attr($user->displayName ?? ''); ?>"
                                            title="<?php esc_attr_e('Change password', 'mf-staffcore-lite'); ?>"
                                        >
                                            <?php TablerIcon::print('key'); ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canDelete && ! $user->isProtected()) : ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline btn-danger"
                                                data-action="open-delete"
                                                data-id="<?php echo esc_attr((string) $user->id); ?>"
                                                data-label="<?php echo esc_attr($user->displayName ?? ''); ?>"
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
    <form class="needs-validation" data-role="user-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="user-display-name"><?php esc_html_e('Display name', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="user-display-name" name="display_name" maxlength="250" required>
                <div class="invalid-feedback"><?php esc_html_e('Display name is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="user-email"><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></label>
                <input type="email" class="form-control" id="user-email" name="user_email" maxlength="255" required>
                <div class="invalid-feedback"><?php esc_html_e('A valid email is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="user-login"><?php esc_html_e('Username', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="user-login" name="user_login" maxlength="60" required autocomplete="off">
                <div class="invalid-feedback"><?php esc_html_e('Username is required.', 'mf-staffcore-lite'); ?></div>
                <div class="form-text text-secondary d-none" data-role="login-readonly-hint">
                    <?php esc_html_e('Username cannot be changed after the account is created.', 'mf-staffcore-lite'); ?>
                </div>
            </div>
            <div class="col-md-6" data-role="password-field-wrap">
                <label class="form-label required" for="user-password"><?php esc_html_e('Password', 'mf-staffcore-lite'); ?></label>
                <input type="password" class="form-control" id="user-password" name="password" minlength="8" autocomplete="new-password">
                <div class="invalid-feedback"><?php esc_html_e('Password must be at least 8 characters.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="user-role"><?php esc_html_e('Role', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="user-role" name="role" required>
                    <option value=""><?php esc_html_e('Select role', 'mf-staffcore-lite'); ?></option>
                    <?php UsersPresenter::roleOptions(); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Role is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-12 d-none" data-role="manager-scope-wrap">
                <label class="form-label" for="user-manager-scope"><?php esc_html_e('Data access scope', 'mf-staffcore-lite'); ?></label>
                <select
                    class="form-select w-100"
                    id="user-manager-scope"
                    name="manager_scope_mode"
                    data-staffcore-search="off"
                >
                    <?php UsersPresenter::managerScopeModeOptions(); ?>
                </select>
                <div data-role="manager-scope-help">
                    <?php echo wp_kses_post(UsersPresenter::managerScopeHelpHtml()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in presenter ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-phone"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="user-phone" name="phone" maxlength="20">
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="user-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="user-status" name="status" required>
                    <?php UsersPresenter::statusOptions(); ?>
                </select>
            </div>
        </div>
        <div class="alert alert-warning d-none mt-3 mb-0" data-role="form-warning" role="alert"></div>
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

    $modalId = 'staffcore-user-form-modal';
    $title = __('User', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $formBody;
    $footerHtml = $formFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canCreate) :
    ob_start();
    ?>
    <form class="needs-validation" data-role="link-form" data-staffcore-validate novalidate>
        <input type="hidden" name="platform_user_id" value="">
        <div class="mb-3">
            <label class="form-label required" for="link-user-search"><?php esc_html_e('WordPress user', 'mf-staffcore-lite'); ?></label>
            <input
                type="search"
                class="form-control"
                id="link-user-search"
                name="link_search"
                autocomplete="off"
                placeholder="<?php esc_attr_e('Search by name, username, or email…', 'mf-staffcore-lite'); ?>"
            >
            <div class="form-text text-secondary">
                <?php esc_html_e('Only accounts not already linked to StaffCore are listed.', 'mf-staffcore-lite'); ?>
            </div>
            <div
                class="list-group list-group-flush staffcore-link-user-results d-none mt-2"
                data-role="link-user-results"
                role="listbox"
                aria-label="<?php esc_attr_e('WordPress users', 'mf-staffcore-lite'); ?>"
            ></div>
            <p class="text-secondary small mb-0 mt-2 d-none" data-role="link-user-empty">
                <?php esc_html_e('No matching WordPress users found.', 'mf-staffcore-lite'); ?>
            </p>
            <div class="alert alert-info d-none mt-2 mb-0 py-2" data-role="link-user-selected" role="status"></div>
            <div class="invalid-feedback d-block d-none" data-role="link-user-validation">
                <?php esc_html_e('Select a WordPress user to link.', 'mf-staffcore-lite'); ?>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="link-user-role"><?php esc_html_e('Role', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="link-user-role" name="role" required>
                    <option value=""><?php esc_html_e('Select role', 'mf-staffcore-lite'); ?></option>
                    <?php UsersPresenter::roleOptions(); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Role is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="link-user-phone"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="link-user-phone" name="phone" maxlength="20">
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="link-user-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="link-user-status" name="status" required>
                    <?php UsersPresenter::statusOptions(); ?>
                </select>
            </div>
        </div>
        <div class="alert alert-danger d-none mt-3 mb-0" data-role="link-form-error" role="alert"></div>
    </form>
    <?php
    $linkBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-success" data-action="submit-link">
        <span><?php esc_html_e('Link user', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="link-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $linkFooter = ob_get_clean();

    $modalId = 'staffcore-user-link-modal';
    $title = __('Link existing WordPress user', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $linkBody;
    $footerHtml = $linkFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';

    ob_start();
    ?>
    <form class="needs-validation" data-role="bulk-link-form" data-staffcore-validate novalidate>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="bulk-link-source"><?php esc_html_e('Candidate group', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="bulk-link-source" name="source">
                    <option value="<?php echo esc_attr(LinkCandidateFilter::SOURCE_UNLINKED_WITH_EMPLOYEE); ?>">
                        <?php esc_html_e('Has employee record, not in StaffCore', 'mf-staffcore-lite'); ?>
                    </option>
                    <option value="<?php echo esc_attr(LinkCandidateFilter::SOURCE_UNLINKED_ALL); ?>">
                        <?php esc_html_e('All WordPress users not in StaffCore', 'mf-staffcore-lite'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bulk-link-search"><?php esc_html_e('Search', 'mf-staffcore-lite'); ?></label>
                <input
                    type="search"
                    class="form-control"
                    id="bulk-link-search"
                    name="search"
                    autocomplete="off"
                    placeholder="<?php esc_attr_e('Name, username, or email…', 'mf-staffcore-lite'); ?>"
                >
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <p class="text-secondary small mb-0" data-role="bulk-link-selection-summary">
                <?php esc_html_e('0 selected', 'mf-staffcore-lite'); ?>
            </p>
            <span class="spinner-border spinner-border-sm d-none ms-auto" data-role="bulk-link-loading" aria-hidden="true"></span>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-vcenter table-sm mb-0" data-role="bulk-link-table">
                <thead>
                    <tr>
                        <th class="w-1">
                            <input
                                type="checkbox"
                                class="form-check-input m-0 align-middle"
                                data-action="bulk-select-page"
                                aria-label="<?php esc_attr_e('Select all on this page', 'mf-staffcore-lite'); ?>"
                            >
                        </th>
                        <th><?php esc_html_e('User', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Employee', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('WP role', 'mf-staffcore-lite'); ?></th>
                    </tr>
                </thead>
                <tbody data-role="bulk-link-table-body">
                    <tr data-role="bulk-link-empty-row">
                        <td colspan="4" class="text-secondary text-center py-4">
                            <?php esc_html_e('No candidates found.', 'mf-staffcore-lite'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="d-flex flex-wrap align-items-center gap-2 mt-2 py-2 d-none"
            data-role="bulk-link-pagination"
        >
            <p class="m-0 text-secondary small" data-role="bulk-link-pagination-summary"></p>
            <ul class="pagination pagination-sm m-0 ms-auto" data-role="bulk-link-pagination-list"></ul>
        </div>

        <hr class="my-3">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required" for="bulk-link-role"><?php esc_html_e('StaffCore role', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="bulk-link-role" name="role" required>
                    <option value=""><?php esc_html_e('Select role', 'mf-staffcore-lite'); ?></option>
                    <?php UsersPresenter::roleOptions(); ?>
                </select>
                <div class="invalid-feedback"><?php esc_html_e('Role is required.', 'mf-staffcore-lite'); ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bulk-link-phone"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></label>
                <input type="text" class="form-control" id="bulk-link-phone" name="phone" maxlength="20">
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="bulk-link-status"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></label>
                <select class="form-select w-100" id="bulk-link-status" name="status" required>
                    <?php UsersPresenter::statusOptions(); ?>
                </select>
            </div>
        </div>

        <div class="alert alert-info d-none mt-3 mb-0 py-2" data-role="bulk-link-selection-validation" role="alert">
            <?php esc_html_e('Select at least one user to link.', 'mf-staffcore-lite'); ?>
        </div>
        <div class="alert alert-danger d-none mt-3 mb-0" data-role="bulk-link-form-error" role="alert"></div>
    </form>
    <?php
    $bulkLinkBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-success" data-action="submit-bulk-link">
        <span><?php esc_html_e('Link selected', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="bulk-link-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $bulkLinkFooter = ob_get_clean();

    $modalId = 'staffcore-user-bulk-link-modal';
    $title = __('Bulk link WordPress users', 'mf-staffcore-lite');
    $size = 'modal-lg';
    $bodyHtml = $bulkLinkBody;
    $footerHtml = $bulkLinkFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';

    endif;

    if ($canEdit) :
    ob_start();
    ?>
    <form class="needs-validation" data-role="password-form" data-staffcore-validate novalidate>
        <input type="hidden" name="id" value="">
        <p class="text-secondary mb-3">
            <?php esc_html_e('Set a new password for', 'mf-staffcore-lite'); ?>
            <strong data-role="password-user-label"></strong>
        </p>
        <div class="mb-3">
            <label class="form-label required" for="user-new-password"><?php esc_html_e('New password', 'mf-staffcore-lite'); ?></label>
            <input type="password" class="form-control" id="user-new-password" name="password" minlength="8" required autocomplete="new-password">
            <div class="invalid-feedback"><?php esc_html_e('Password must be at least 8 characters.', 'mf-staffcore-lite'); ?></div>
        </div>
        <div class="mb-0">
            <label class="form-label required" for="user-password-confirm"><?php esc_html_e('Confirm password', 'mf-staffcore-lite'); ?></label>
            <input type="password" class="form-control" id="user-password-confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
            <div class="invalid-feedback"><?php esc_html_e('Passwords must match.', 'mf-staffcore-lite'); ?></div>
        </div>
        <div class="alert alert-danger d-none mt-3 mb-0" data-role="password-error" role="alert"></div>
    </form>
    <?php
    $passwordBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-success" data-action="submit-password">
        <span><?php esc_html_e('Update password', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="password-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $passwordFooter = ob_get_clean();

    $modalId = 'staffcore-user-password-modal';
    $title = __('Change password', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $passwordBody;
    $footerHtml = $passwordFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    if ($canDelete) :
    ob_start();
    ?>
    <p class="mb-0">
        <?php esc_html_e('Are you sure you want to delete this user?', 'mf-staffcore-lite'); ?>
        <strong data-role="delete-label"></strong>
    </p>
    <div class="alert alert-danger d-none mt-3 mb-0" data-role="delete-error" role="alert"></div>
    <?php
    $deleteBody = ob_get_clean();

    ob_start();
    ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?></button>
    <button type="button" class="btn btn-outline btn-danger" data-action="confirm-delete">
        <span><?php esc_html_e('Delete', 'mf-staffcore-lite'); ?></span>
        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="delete-spinner" aria-hidden="true"></span>
    </button>
    <?php
    $deleteFooter = ob_get_clean();

    $modalId = 'staffcore-user-delete-modal';
    $title = __('Delete user', 'mf-staffcore-lite');
    $size = '';
    $bodyHtml = $deleteBody;
    $footerHtml = $deleteFooter;
    include MF_STAFFCORE_PATH . 'ui/components/modal.php';
    endif;

    include MF_STAFFCORE_PATH . 'ui/components/bulk-delete-modal.php';
    ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

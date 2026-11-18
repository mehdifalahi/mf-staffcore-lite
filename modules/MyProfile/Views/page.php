<?php

declare(strict_types=1);

/**
 * @var \MF\StaffCore\Modules\Employees\DTO\Employee|null $employee
 * @var string|null $scopeError
 * @var int $passwordMin
 * @var string $changePasswordNonce
 * @var string $ajaxUrl
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\EmployeesPresenter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\MyProfilePresenter;
use MF\StaffCore\UI\PersonAvatar;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div
    class="staffcore-my-profile"
    data-staffcore-module="my-profile"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($changePasswordNonce); ?>"
    data-password-min="<?php echo esc_attr((string) $passwordMin); ?>"
    <?php if ($employee !== null) : ?>
        data-employee-name="<?php echo esc_attr($employee->fullName()); ?>"
        data-photo-url="<?php echo esc_attr($employee->photo ?? ''); ?>"
    <?php endif; ?>
>
    <div class="toast-container position-fixed top-0 end-0 p-3" data-role="toast-container"></div>

    <?php if (! empty($scopeError)) : ?>
        <div class="alert alert-warning mb-3" role="alert"><?php echo esc_html($scopeError); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <?php if ($employee !== null) : ?>
            <div class="col-lg-4 d-flex">
                <div class="card staffcore-my-profile-summary h-100 w-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <div class="staffcore-my-profile-photo mb-3">
                            <button
                                type="button"
                                class="staffcore-profile-photo-trigger"
                                data-action="open-photo-modal"
                                aria-label="<?php esc_attr_e('Change profile photo', 'mf-staffcore-lite'); ?>"
                            >
                                <span class="staffcore-profile-photo-frame" data-role="profile-avatar">
                                    <?php echo wp_kses_post(PersonAvatar::spanHtml(
                                        $employee->fullName(),
                                        $employee->photo,
                                        'avatar avatar-xl',
                                    )); ?>
                                    <span class="staffcore-profile-photo-overlay" aria-hidden="true">
                                        <?php TablerIcon::print('camera', 'staffcore-profile-photo-overlay-icon'); ?>
                                        <span class="staffcore-profile-photo-overlay-text">
                                            <?php esc_html_e('Change photo', 'mf-staffcore-lite'); ?>
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </div>
                        <h3 class="mb-1"><?php echo esc_html($employee->fullName()); ?></h3>
                        <div class="text-secondary"><?php echo esc_html($employee->code); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="<?php echo $employee !== null ? 'col-lg-8 d-flex' : 'col-12'; ?>">
            <div class="card h-100 w-100">
                <div class="card-header">
                    <h3 class="card-title mb-0"><?php esc_html_e('Change Password', 'mf-staffcore-lite'); ?></h3>
                </div>
                <div class="card-body">
                    <form
                        id="staffcore-my-profile-password-form"
                        class="needs-validation staffcore-my-profile-password-form"
                        novalidate
                        data-staffcore-validate
                    >
                        <div class="alert alert-danger d-none mb-3" role="alert" data-role="password-error"></div>

                        <div class="mb-3">
                            <label class="form-label required" for="staffcore-my-profile-current-password">
                                <?php esc_html_e('Current Password', 'mf-staffcore-lite'); ?>
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="staffcore-my-profile-current-password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                            <div class="invalid-feedback">
                                <?php esc_html_e('Current password is required.', 'mf-staffcore-lite'); ?>
                            </div>
                            <div class="form-hint">
                                <?php esc_html_e('Enter your current password to verify your identity.', 'mf-staffcore-lite'); ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required" for="staffcore-my-profile-new-password">
                                    <?php esc_html_e('New Password', 'mf-staffcore-lite'); ?>
                                </label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="staffcore-my-profile-new-password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    minlength="<?php echo esc_attr((string) $passwordMin); ?>"
                                    required
                                >
                                <div class="invalid-feedback">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            /* translators: %d: minimum password length */
                                            __('Password must be at least %d characters.', 'mf-staffcore-lite'),
                                            $passwordMin
                                        )
                                    );
                                    ?>
                                </div>
                                <div class="form-hint">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            /* translators: %d: minimum password length */
                                            __('Minimum %d characters.', 'mf-staffcore-lite'),
                                            $passwordMin
                                        )
                                    );
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required" for="staffcore-my-profile-new-password-confirm">
                                    <?php esc_html_e('Confirm New Password', 'mf-staffcore-lite'); ?>
                                </label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="staffcore-my-profile-new-password-confirm"
                                    name="new_password_confirm"
                                    autocomplete="new-password"
                                    required
                                >
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Password confirmation does not match.', 'mf-staffcore-lite'); ?>
                                </div>
                                <div class="form-hint">
                                    <?php esc_html_e('Re-enter your new password.', 'mf-staffcore-lite'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-success" data-action="submit-password">
                                <?php TablerIcon::print('key', 'me-1'); ?>
                                <?php esc_html_e('Change Password', 'mf-staffcore-lite'); ?>
                                <span class="spinner-border spinner-border-sm ms-1 d-none" role="status" data-role="password-spinner"></span>
                            </button>
                            <button type="button" class="btn btn-secondary" data-action="cancel-password">
                                <?php TablerIcon::print('arrow-left', 'me-1'); ?>
                                <?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($employee !== null) : ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><?php esc_html_e('Profile Details', 'mf-staffcore-lite'); ?></h3>
            </div>
            <div class="card-body">
                <h4 class="text-secondary mb-3"><?php esc_html_e('Work Information', 'mf-staffcore-lite'); ?></h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(EmployeesPresenter::departmentBadge($employee->departmentName)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Position', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(EmployeesPresenter::positionCellHtml($employee)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(EmployeesPresenter::statusBadge($employee->employmentStatus)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Hire Date', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(MyProfilePresenter::formatDate($employee->hireDate)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Employment Type', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(MyProfilePresenter::employmentTypeLabel($employee->employmentType)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Annual Leave', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(EmployeesPresenter::leaveDaysBadge($employee->annualLeave, 'annual')); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Remaining Leave', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(EmployeesPresenter::leaveDaysBadge($employee->remainingLeave, 'remaining')); ?></div>
                    </div>
                </div>

                <h4 class="text-secondary mb-3"><?php esc_html_e('Contact', 'mf-staffcore-lite'); ?></h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(MyProfilePresenter::displayValue($employee->email)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(MyProfilePresenter::displayValue($employee->phone)); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small mb-1"><?php esc_html_e('Birth Date', 'mf-staffcore-lite'); ?></div>
                        <div><?php echo wp_kses_post(MyProfilePresenter::formatDate($employee->birthDate)); ?></div>
                    </div>
                </div>

                <?php if (
                    $employee->address !== null
                    || $employee->city !== null
                    || $employee->province !== null
                    || $employee->country !== null
                    || $employee->zipCode !== null
                ) : ?>
                    <h4 class="text-secondary mb-3"><?php esc_html_e('Address', 'mf-staffcore-lite'); ?></h4>
                    <div class="row g-3">
                        <?php if ($employee->address !== null && $employee->address !== '') : ?>
                            <div class="col-12">
                                <div class="text-secondary small mb-1"><?php esc_html_e('Street', 'mf-staffcore-lite'); ?></div>
                                <div><?php echo wp_kses_post(MyProfilePresenter::displayValue($employee->address)); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <div class="text-secondary small mb-1"><?php esc_html_e('City', 'mf-staffcore-lite'); ?></div>
                            <div><?php echo wp_kses_post(MyProfilePresenter::displayValue($employee->city)); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary small mb-1"><?php esc_html_e('Province/State', 'mf-staffcore-lite'); ?></div>
                            <div><?php echo wp_kses_post(MyProfilePresenter::displayValue($employee->province)); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary small mb-1"><?php esc_html_e('Country', 'mf-staffcore-lite'); ?></div>
                            <div><?php echo wp_kses_post(MyProfilePresenter::displayValue($employee->country)); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($employee !== null) : ?>
        <?php
        $modalId = 'staffcore-my-profile-photo-modal';
        $title = __('Profile Photo', 'mf-staffcore-lite');
        $size = 'modal-lg';
        $modalExtraClass = 'staffcore-photo-crop-modal';
        $dialogExtraClass = 'modal-dialog-scrollable staffcore-photo-crop-modal-dialog';
        $dialogCentered = false;
        ob_start();
        ?>
        <div class="alert alert-danger d-none mb-3" role="alert" data-role="photo-error"></div>

        <div class="staffcore-photo-crop-stage d-none" data-role="photo-crop-stage">
            <div class="staffcore-photo-crop-wrap" data-role="photo-crop-wrap">
                <img
                    src=""
                    alt="<?php esc_attr_e('Photo preview', 'mf-staffcore-lite'); ?>"
                    class="staffcore-photo-crop-image"
                    data-role="photo-crop-image"
                >
            </div>
            <div class="staffcore-photo-crop-toolbar d-flex flex-wrap gap-2 mt-2 justify-content-center">
                <button type="button" class="btn btn-outline btn-secondary btn-sm" data-action="photo-zoom-in">
                    <?php TablerIcon::print('zoom-in', 'me-1'); ?>
                    <?php esc_html_e('Zoom in', 'mf-staffcore-lite'); ?>
                </button>
                <button type="button" class="btn btn-outline btn-secondary btn-sm" data-action="photo-zoom-out">
                    <?php TablerIcon::print('zoom-out', 'me-1'); ?>
                    <?php esc_html_e('Zoom out', 'mf-staffcore-lite'); ?>
                </button>
                <button type="button" class="btn btn-outline btn-secondary btn-sm" data-action="photo-rotate-left">
                    <?php TablerIcon::print('rotate', 'me-1'); ?>
                    <?php esc_html_e('Rotate', 'mf-staffcore-lite'); ?>
                </button>
            </div>
        </div>

        <div class="text-center" data-role="photo-pick-stage">
            <p class="text-secondary mb-3">
                <?php esc_html_e('Upload a square profile photo. JPEG, PNG, or WebP up to 5 MB.', 'mf-staffcore-lite'); ?>
            </p>
            <label class="btn btn-outline btn-secondary mb-0" for="staffcore-my-profile-photo-input">
                <?php TablerIcon::print('upload', 'me-1'); ?>
                <?php esc_html_e('Choose image', 'mf-staffcore-lite'); ?>
            </label>
            <input
                type="file"
                id="staffcore-my-profile-photo-input"
                class="d-none"
                accept="image/jpeg,image/png,image/webp"
                data-role="photo-file-input"
            >
        </div>
        <?php
        $bodyHtml = (string) ob_get_clean();
        ob_start();
        ?>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <?php esc_html_e('Cancel', 'mf-staffcore-lite'); ?>
        </button>
        <button type="button" class="btn btn-success d-none" data-action="save-photo" data-role="photo-save-btn">
            <?php TablerIcon::print('check', 'me-1'); ?>
            <?php esc_html_e('Save Photo', 'mf-staffcore-lite'); ?>
            <span class="spinner-border spinner-border-sm ms-1 d-none" role="status" data-role="photo-spinner"></span>
        </button>
        <?php
        $footerHtml = (string) ob_get_clean();
        include MF_STAFFCORE_PATH . 'ui/components/modal.php';
        ?>
    <?php endif; ?>
</div>

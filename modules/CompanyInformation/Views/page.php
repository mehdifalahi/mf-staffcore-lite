<?php

declare(strict_types=1);

/**
 * @var \MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformation|null $profile
 * @var list<string> $currencies
 * @var list<string> $timezones
 * @var string $ajaxUrl
 * @var string $nonce
 * @var bool $canEdit
 * @var bool $showUpgradeUi
 * @var string $settingsUpgradeUrl
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\CompanyInformationPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$profilePayload = CompanyInformationPresenter::profilePayload($profile);
$hasLogo = $profile !== null && $profile->logo !== null && $profile->logo !== '';
?>
<div
    class="staffcore-company-information"
    data-staffcore-module="company-information"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-can-edit="<?php echo $canEdit ? '1' : '0'; ?>"
    data-profile="<?php echo esc_attr(wp_json_encode($profilePayload)); ?>"
>
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1"><?php esc_html_e('Company Information', 'mf-staffcore-lite'); ?></h3>
                <p class="text-secondary mb-0 small">
                    <?php esc_html_e('Company profile used in documents, payroll, and system branding.', 'mf-staffcore-lite'); ?>
                </p>
            </div>
        </div>
        <div class="card-body">
            <?php if (! $canEdit) : ?>
                <p class="text-secondary mb-3"><?php esc_html_e('You can view company information but cannot modify it.', 'mf-staffcore-lite'); ?></p>
            <?php endif; ?>
            <form
                class="needs-validation"
                data-role="company-information-form"
                data-staffcore-validate
                novalidate
                enctype="multipart/form-data"
            >
                <input type="hidden" name="id" value="<?php echo esc_attr($profile !== null ? (string) $profile->id : ''); ?>">
                <input type="hidden" name="logo_current" value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'logo')); ?>">

                <h4 class="mb-3"><?php esc_html_e('Company Profile', 'mf-staffcore-lite'); ?></h4>
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label required" for="company-name"><?php esc_html_e('Company Name', 'mf-staffcore-lite'); ?></label>
                            <input
                                type="text"
                                class="form-control"
                                id="company-name"
                                name="name"
                                maxlength="255"
                                required
                                value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'name')); ?>"
                                autocomplete="organization"
                                <?php disabled(! $canEdit); ?>
                            >
                            <div class="invalid-feedback"><?php esc_html_e('Company name is required.', 'mf-staffcore-lite'); ?></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="company-email"><?php esc_html_e('Email', 'mf-staffcore-lite'); ?></label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="company-email"
                                    name="email"
                                    maxlength="255"
                                    value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'email')); ?>"
                                    <?php disabled(! $canEdit); ?>
                                >
                                <div class="invalid-feedback"><?php esc_html_e('Enter a valid email address.', 'mf-staffcore-lite'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="company-phone"><?php esc_html_e('Phone', 'mf-staffcore-lite'); ?></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="company-phone"
                                    name="phone"
                                    maxlength="20"
                                    value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'phone')); ?>"
                                    <?php disabled(! $canEdit); ?>
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="company-website"><?php esc_html_e('Website', 'mf-staffcore-lite'); ?></label>
                                <input
                                    type="url"
                                    class="form-control"
                                    id="company-website"
                                    name="website"
                                    maxlength="255"
                                    placeholder="https://"
                                    value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'website')); ?>"
                                    <?php disabled(! $canEdit); ?>
                                >
                                <div class="invalid-feedback"><?php esc_html_e('Enter a valid website URL.', 'mf-staffcore-lite'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="company-logo"><?php esc_html_e('Company Logo', 'mf-staffcore-lite'); ?></label>
                        <div class="d-flex flex-column align-items-start gap-3">
                            <div data-role="logo-preview" class="<?php echo $hasLogo ? '' : 'd-none'; ?>">
                                <?php echo wp_kses_post(CompanyInformationPresenter::logoPreviewHtml($profile)); ?>
                            </div>
                            <input type="file" class="form-control" id="company-logo" name="logo" accept="image/*" <?php disabled(! $canEdit); ?>>
                            <div class="form-text text-secondary">
                                <?php esc_html_e('PNG, JPG or WebP. Used in generated documents.', 'mf-staffcore-lite'); ?>
                            </div>
                            <?php if ($hasLogo && $canEdit) : ?>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline btn-danger"
                                    data-action="remove-logo"
                                >
                                    <?php TablerIcon::print('trash', 'me-1'); ?>
                                    <?php esc_html_e('Remove logo', 'mf-staffcore-lite'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h4 class="mb-3"><?php esc_html_e('Address', 'mf-staffcore-lite'); ?></h4>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="company-address"><?php esc_html_e('Street Address', 'mf-staffcore-lite'); ?></label>
                        <textarea
                            class="form-control"
                            id="company-address"
                            name="address"
                            rows="2"
                            <?php disabled(! $canEdit); ?>
                        ><?php echo esc_textarea(CompanyInformationPresenter::fieldValue($profile, 'address')); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="company-city"><?php esc_html_e('City', 'mf-staffcore-lite'); ?></label>
                        <input
                            type="text"
                            class="form-control"
                            id="company-city"
                            name="city"
                            maxlength="100"
                            value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'city')); ?>"
                            <?php disabled(! $canEdit); ?>
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="company-province"><?php esc_html_e('Province / State', 'mf-staffcore-lite'); ?></label>
                        <input
                            type="text"
                            class="form-control"
                            id="company-province"
                            name="province"
                            maxlength="100"
                            value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'province')); ?>"
                            <?php disabled(! $canEdit); ?>
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="company-country"><?php esc_html_e('Country', 'mf-staffcore-lite'); ?></label>
                        <input
                            type="text"
                            class="form-control"
                            id="company-country"
                            name="country"
                            maxlength="100"
                            value="<?php echo esc_attr(CompanyInformationPresenter::fieldValue($profile, 'country')); ?>"
                            <?php disabled(! $canEdit); ?>
                        >
                    </div>
                </div>

                <hr class="my-4">
                <h4 class="mb-3"><?php esc_html_e('Regional Settings', 'mf-staffcore-lite'); ?></h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="company-currency"><?php esc_html_e('Default Currency', 'mf-staffcore-lite'); ?></label>
                        <select class="form-select w-100" id="company-currency" name="currency" <?php disabled(! $canEdit); ?>>
                            <?php CompanyInformationPresenter::currencyOptions(
                                $currencies,
                                CompanyInformationPresenter::fieldValue($profile, 'currency') ?: null,
                            ); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="company-timezone"><?php esc_html_e('Timezone', 'mf-staffcore-lite'); ?></label>
                        <select class="form-select w-100" id="company-timezone" name="timezone" <?php disabled(! $canEdit); ?>>
                            <?php CompanyInformationPresenter::timezoneOptions(
                                $timezones,
                                CompanyInformationPresenter::fieldValue($profile, 'timezone') ?: null,
                            ); ?>
                        </select>
                    </div>
                </div>

                <div class="alert alert-danger d-none mt-4 mb-0" data-role="form-error" role="alert"></div>

                <?php if ($canEdit) : ?>
                <div class="d-flex justify-content-end mt-4 pt-2 border-top">
                    <button type="button" class="btn btn-success" data-action="submit-form">
                        <?php TablerIcon::print('device-floppy', 'me-1'); ?>
                        <span data-role="submit-label"><?php esc_html_e('Save Changes', 'mf-staffcore-lite'); ?></span>
                        <span class="spinner-border spinner-border-sm d-none ms-1" data-role="submit-spinner" aria-hidden="true"></span>
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (! empty($showUpgradeUi)) :
        $upgradeUrl = $settingsUpgradeUrl;
        include MF_STAFFCORE_PATH . 'ui/components/pro-upsell-card.php';
    endif; ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>

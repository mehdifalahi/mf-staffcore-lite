<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformation;
use MF\StaffCore\Platforms\WordPress\Ajax\CompanyInformationAjaxHandler;

final class CompanyInformationPresenter
{
    public function render(): void
    {
        $service = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance()->companyInformation();
        $profile = $service->getProfile();
        $currencies = $service->currencyOptions();
        $timezones = $service->timezoneOptions();
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = CompanyInformationAjaxHandler::nonce();
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $perms = $container->authorization()->modulePermissions('company_information');
        $canEdit = $perms->edit;
        $showUpgradeUi = \MF\StaffCore\Plan\PlanRegistry::shouldShowUpgradeUi();
        $settingsUpgradeUrl = \MF\StaffCore\Plan\PlanRegistry::upgradeUrl('settings_card');

        include MF_STAFFCORE_PATH . 'modules/CompanyInformation/Views/page.php';
    }

    /**
     * @return array<string, mixed>
     */
    public static function profilePayload(?CompanyInformation $profile): array
    {
        if ($profile === null) {
            return [
                'id' => '',
                'name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'city' => '',
                'province' => '',
                'country' => '',
                'website' => '',
                'currency' => '',
                'timezone' => '',
                'logo' => '',
            ];
        }

        return $profile->toArray();
    }

    public static function fieldValue(?CompanyInformation $profile, string $field): string
    {
        if ($profile === null) {
            return '';
        }

        $value = $profile->toArray()[$field] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param list<string> $currencies
     */
    public static function currencyOptions(array $currencies, ?string $selected): void
    {
        printf('<option value="">%s</option>', esc_html__('Select currency', 'mf-staffcore-lite'));

        foreach ($currencies as $currency) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($currency),
                selected($selected, $currency, false),
                esc_html($currency),
            );
        }
    }

    /**
     * @param list<string> $timezones
     */
    public static function timezoneOptions(array $timezones, ?string $selected): void
    {
        printf('<option value="">%s</option>', esc_html__('Select timezone', 'mf-staffcore-lite'));

        if ($selected !== null && $selected !== '' && ! in_array($selected, $timezones, true)) {
            printf(
                '<option value="%s" selected>%s</option>',
                esc_attr($selected),
                esc_html($selected),
            );
        }

        foreach ($timezones as $timezone) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($timezone),
                selected($selected, $timezone, false),
                esc_html($timezone),
            );
        }
    }

    public static function logoPreviewHtml(?CompanyInformation $profile): string
    {
        if ($profile === null || $profile->logo === null || $profile->logo === '') {
            return '';
        }

        return sprintf(
            '<span class="avatar avatar-xl rounded" style="background-image: url(%s)" role="img" aria-label="%s"></span>',
            esc_url($profile->logo),
            esc_attr__('Company logo', 'mf-staffcore-lite'),
        );
    }
}

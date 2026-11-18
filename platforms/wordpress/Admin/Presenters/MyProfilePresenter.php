<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\UI\PersonAvatar;

final class MyProfilePresenter
{
    public function render(): void
    {
        $container = ServiceContainer::instance();
        $auth = $container->authorization();
        $context = $auth->current();

        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            echo '<div class="alert alert-warning" role="alert">';
            esc_html_e('My Profile is only available for employees.', 'mf-staffcore-lite');
            echo '</div>';

            return;
        }

        $employee = $container->myProfile()->linkedProfile($context->platformUserId);
        $scopeError = null;

        if ($employee === null) {
            $scopeError = __(
                'No employee record is linked to your account. Contact HR to complete your profile.',
                'mf-staffcore-lite',
            );
        }

        $passwordMin = \MF\StaffCore\Modules\MyProfile\Services\MyProfileService::PASSWORD_MIN;
        $changePasswordNonce = wp_create_nonce('mf_staffcore_my_profile');
        $ajaxUrl = admin_url('admin-ajax.php');

        include MF_STAFFCORE_PATH . 'modules/MyProfile/Views/page.php';
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(gmdate('M j, Y', $timestamp));
    }

    public static function employmentTypeLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return '—';
        }

        return esc_html(Employee::typeLabel($type));
    }

    public static function profileHeaderHtml(Employee $employee): string
    {
        return PersonAvatar::cellHtml(
            $employee->fullName(),
            $employee->photo,
            $employee->email,
        );
    }
}

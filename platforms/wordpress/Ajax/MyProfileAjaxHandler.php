<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Platforms\WordPress\Media\StaffCoreImageUploader;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class MyProfileAjaxHandler
{
    use StaffCoreAjaxAuthorization;

    private const NONCE_ACTION = 'mf_staffcore_my_profile';
    private const MODULE = 'my_profile';

    public function changePassword(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $container = ServiceContainer::instance();
        $auth = $container->authorization();
        $context = $auth->current();

        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            wp_send_json_error(['message' => __('My Profile is only available for employees.', 'mf-staffcore-lite')], 403);
        }

        $currentPassword = isset($_POST['current_password'])
            ? (string) wp_unslash($_POST['current_password'])
            : '';
        $newPassword = isset($_POST['new_password'])
            ? (string) wp_unslash($_POST['new_password'])
            : '';
        $newPasswordConfirm = isset($_POST['new_password_confirm'])
            ? (string) wp_unslash($_POST['new_password_confirm'])
            : '';

        try {
            $container->myProfile()->changeOwnPassword(
                $context->platformUserId,
                $currentPassword,
                $newPassword,
                $newPasswordConfirm,
            );

            wp_send_json_success([
                'message' => __('Password updated successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function uploadPhoto(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $container = ServiceContainer::instance();
        $auth = $container->authorization();
        $context = $auth->current();

        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            wp_send_json_error(['message' => __('My Profile is only available for employees.', 'mf-staffcore-lite')], 403);
        }

        if (! isset($_FILES['photo']) || ! is_array($_FILES['photo'])) {
            wp_send_json_error(['message' => __('No image file was uploaded.', 'mf-staffcore-lite')], 400);
        }

        try {
            $photoUrl = StaffCoreImageUploader::uploadEmployeePhoto($_FILES['photo']);
            $employee = $container->myProfile()->updateProfilePhoto(
                $context->platformUserId,
                $photoUrl,
                $context->platformUserId,
            );

            wp_send_json_success([
                'photo' => $employee->photo,
                'message' => __('Profile photo updated successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }
}

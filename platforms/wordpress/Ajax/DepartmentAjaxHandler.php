<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Departments\Services\DepartmentService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\DepartmentsPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class DepartmentAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;

    private const NONCE_ACTION = 'mf_staffcore_departments';
    private const MODULE = 'departments';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());

            if ($id > 0) {
                $department = $this->service()->update($id, $input, $userId);
            } else {
                $department = $this->service()->create($input, $userId);
            }

            wp_send_json_success([
                'department' => DepartmentsPresenter::departmentPayload($department),
                'message' => $id > 0
                    ? __('Department updated successfully.', 'mf-staffcore-lite')
                    : __('Department created successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function delete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid department ID.'], 400);
        }

        try {
            $this->service()->delete($id);

            wp_send_json_success([
                'message' => __('Department deleted successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function bulkDelete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        try {
            $ids = $this->readBulkDeleteIdsFromPost();
            $result = $this->service()->bulkDelete($ids, get_current_user_id());
            $this->sendBulkDeleteResponse(
                $result,
                __('department', 'mf-staffcore-lite'),
                __('departments', 'mf-staffcore-lite'),
            );
        } catch (ValidationException | RuntimeException $exception) {
            $this->handleBulkDeleteFailure($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readInputFromPost(): array
    {
        return [
            'name' => isset($_POST['name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['name']))
                : '',
            'code' => isset($_POST['code'])
                ? sanitize_text_field(wp_unslash((string) $_POST['code']))
                : '',
            'description' => isset($_POST['description'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['description']))
                : '',
            'manager_id' => isset($_POST['manager_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['manager_id']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'ACTIVE',
        ];
    }

    private function service(): DepartmentService
    {
        return ServiceContainer::instance()->departments();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

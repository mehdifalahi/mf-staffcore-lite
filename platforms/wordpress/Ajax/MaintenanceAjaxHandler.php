<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Maintenance\Services\MaintenanceService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\MaintenancePresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class MaintenanceAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;

    private const NONCE_ACTION = 'mf_staffcore_maintenance';
    private const MODULE = 'maintenance';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());

            if ($id > 0) {
                $maintenance = $this->service()->update($id, $input, $userId);
            } else {
                $maintenance = $this->service()->create($input, $userId);
            }

            wp_send_json_success([
                'maintenance' => MaintenancePresenter::maintenancePayload($maintenance),
                'message' => $id > 0
                    ? __('Maintenance record updated successfully.', 'mf-staffcore-lite')
                    : __('Maintenance record created successfully.', 'mf-staffcore-lite'),
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
            wp_send_json_error(['message' => 'Invalid maintenance record ID.'], 400);
        }

        try {
            $this->service()->delete($id);

            wp_send_json_success([
                'message' => __('Maintenance record deleted successfully.', 'mf-staffcore-lite'),
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
                __('maintenance record', 'mf-staffcore-lite'),
                __('maintenance records', 'mf-staffcore-lite'),
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
            'asset_id' => isset($_POST['asset_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['asset_id']))
                : '',
            'maintenance_date' => isset($_POST['maintenance_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['maintenance_date']))
                : '',
            'type' => isset($_POST['type'])
                ? sanitize_key(wp_unslash((string) $_POST['type']))
                : 'SERVICE',
            'cost' => isset($_POST['cost'])
                ? sanitize_text_field(wp_unslash((string) $_POST['cost']))
                : '',
            'performed_by_employee_id' => isset($_POST['performed_by_employee_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['performed_by_employee_id']))
                : '',
            'next_service_date' => isset($_POST['next_service_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['next_service_date']))
                : '',
            'notes' => isset($_POST['notes'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['notes']))
                : '',
        ];
    }

    private function service(): MaintenanceService
    {
        return ServiceContainer::instance()->maintenance();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

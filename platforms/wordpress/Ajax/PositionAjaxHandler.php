<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Positions\Services\PositionService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PositionsPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class PositionAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;

    private const NONCE_ACTION = 'mf_staffcore_positions';
    private const MODULE = 'positions';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());

            if ($id > 0) {
                $position = $this->service()->update($id, $input, $userId);
            } else {
                $position = $this->service()->create($input, $userId);
            }

            wp_send_json_success([
                'position' => PositionsPresenter::positionPayload($position),
                'message' => $id > 0
                    ? __('Position updated successfully.', 'mf-staffcore-lite')
                    : __('Position created successfully.', 'mf-staffcore-lite'),
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
            wp_send_json_error(['message' => 'Invalid position ID.'], 400);
        }

        try {
            $this->service()->delete($id);

            wp_send_json_success([
                'message' => __('Position deleted successfully.', 'mf-staffcore-lite'),
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
                __('position', 'mf-staffcore-lite'),
                __('positions', 'mf-staffcore-lite'),
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
            'title' => isset($_POST['title'])
                ? sanitize_text_field(wp_unslash((string) $_POST['title']))
                : '',
            'code' => isset($_POST['code'])
                ? sanitize_text_field(wp_unslash((string) $_POST['code']))
                : '',
            'department_id' => isset($_POST['department_id'])
                ? (int) $_POST['department_id']
                : 0,
            'level' => isset($_POST['level'])
                ? sanitize_key(wp_unslash((string) $_POST['level']))
                : 'MID',
            'min_salary' => isset($_POST['min_salary'])
                ? sanitize_text_field(wp_unslash((string) $_POST['min_salary']))
                : '',
            'max_salary' => isset($_POST['max_salary'])
                ? sanitize_text_field(wp_unslash((string) $_POST['max_salary']))
                : '',
            'description' => isset($_POST['description'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['description']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'ACTIVE',
        ];
    }

    private function service(): PositionService
    {
        return ServiceContainer::instance()->positions();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

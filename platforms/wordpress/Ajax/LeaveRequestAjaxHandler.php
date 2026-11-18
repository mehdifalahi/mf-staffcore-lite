<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\LeaveRequests\Services\LeaveRequestService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\LeaveRequestsPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class LeaveRequestAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;
    use StaffCoreEmployeeScopedAjax;

    private const NONCE_ACTION = 'mf_staffcore_leave_requests';
    private const MODULE = 'leave_requests';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());

            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule('leave_requests');

            if ($id > 0) {
                $leaveRequest = $this->service()->update($id, $input, $userId, $scopeEmployeeIds);
            } else {
                $leaveRequest = $this->service()->create($input, $userId, $scopeEmployeeIds);
            }

            wp_send_json_success([
                'leave_request' => LeaveRequestsPresenter::leaveRequestPayload($leaveRequest),
                'message' => $id > 0
                    ? __('Leave request updated successfully.', 'mf-staffcore-lite')
                    : __('Leave request created successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function approve(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid leave request ID.'], 400);
        }

        try {
            $leaveRequest = $this->service()->approve(
                $id,
                get_current_user_id(),
                $this->scopeEmployeeIdsForModule('leave_requests'),
            );

            wp_send_json_success([
                'leave_request' => LeaveRequestsPresenter::leaveRequestPayload($leaveRequest),
                'message' => __('Leave request approved successfully.', 'mf-staffcore-lite'),
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
            wp_send_json_error(['message' => 'Invalid leave request ID.'], 400);
        }

        try {
            $this->service()->delete($id, $this->scopeEmployeeIdsForModule('leave_requests'));

            wp_send_json_success([
                'message' => __('Leave request deleted successfully.', 'mf-staffcore-lite'),
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
            $result = $this->service()->bulkDelete($ids, get_current_user_id(), $this->scopeEmployeeIdsForModule('leave_requests'));
            $this->sendBulkDeleteResponse(
                $result,
                __('leave request', 'mf-staffcore-lite'),
                __('leave requests', 'mf-staffcore-lite'),
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
            'employee_id' => isset($_POST['employee_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['employee_id']))
                : '',
            'leave_type' => isset($_POST['leave_type'])
                ? sanitize_text_field(wp_unslash((string) $_POST['leave_type']))
                : '',
            'start_date' => isset($_POST['start_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['start_date']))
                : '',
            'end_date' => isset($_POST['end_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['end_date']))
                : '',
            'total_days' => isset($_POST['total_days'])
                ? sanitize_text_field(wp_unslash((string) $_POST['total_days']))
                : '',
            'reason' => isset($_POST['reason'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['reason']))
                : '',
            'notes' => isset($_POST['notes'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['notes']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'PENDING',
        ];
    }

    private function service(): LeaveRequestService
    {
        return ServiceContainer::instance()->leaveRequests();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

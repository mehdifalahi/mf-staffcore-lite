<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Attendance\Services\AttendanceService;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\AttendancePresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class AttendanceAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;
    use StaffCoreEmployeeScopedAjax;

    private const NONCE_ACTION = 'mf_staffcore_attendance';
    private const MODULE = 'attendance';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);
        $this->denyEmployeeManualAttendance();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());

            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule('attendance');

            if ($id > 0) {
                $attendance = $this->service()->update($id, $input, $userId, $scopeEmployeeIds);
            } else {
                $attendance = $this->service()->create($input, $userId, $scopeEmployeeIds);
            }

            wp_send_json_success([
                'attendance' => AttendancePresenter::attendancePayload($attendance),
                'message' => $id > 0
                    ? __('Attendance record updated successfully.', 'mf-staffcore-lite')
                    : __('Attendance record created successfully.', 'mf-staffcore-lite'),
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
        $this->denyEmployeeManualAttendance();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid attendance record ID.'], 400);
        }

        try {
            $this->service()->delete($id, $this->scopeEmployeeIdsForModule('attendance'));

            wp_send_json_success([
                'message' => __('Attendance record deleted successfully.', 'mf-staffcore-lite'),
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
        $this->denyEmployeeManualAttendance();

        try {
            $ids = $this->readBulkDeleteIdsFromPost();
            $result = $this->service()->bulkDelete($ids, get_current_user_id(), $this->scopeEmployeeIdsForModule('attendance'));
            $this->sendBulkDeleteResponse(
                $result,
                __('attendance record', 'mf-staffcore-lite'),
                __('attendance records', 'mf-staffcore-lite'),
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
            'attendance_date' => isset($_POST['attendance_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['attendance_date']))
                : '',
            'clock_in' => isset($_POST['clock_in'])
                ? sanitize_text_field(wp_unslash((string) $_POST['clock_in']))
                : '',
            'clock_out' => isset($_POST['clock_out'])
                ? sanitize_text_field(wp_unslash((string) $_POST['clock_out']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'PRESENT',
            'attendance_type' => isset($_POST['attendance_type'])
                ? sanitize_key(wp_unslash((string) $_POST['attendance_type']))
                : 'MANUAL',
            'remarks' => isset($_POST['remarks'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['remarks']))
                : '',
        ];
    }

    private function service(): AttendanceService
    {
        return ServiceContainer::instance()->attendance();
    }

    private function denyEmployeeManualAttendance(): void
    {
        $context = ServiceContainer::instance()->authorization()->current();

        if ($context !== null && $context->role === RoleRegistry::ROLE_EMPLOYEE) {
            wp_send_json_error([
                'message' => __(
                    'Attendance is recorded by the biometric device. Manual changes are not allowed.',
                    'mf-staffcore-lite',
                ),
            ], 403);
        }
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

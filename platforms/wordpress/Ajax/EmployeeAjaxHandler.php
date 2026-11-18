<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Employees\Services\EmployeeService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\EmployeesPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class EmployeeAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;
    use StaffCoreEmployeeScopedAjax;
    use StaffCorePlanAjaxResponse;

    private const NONCE_ACTION = 'mf_staffcore_employees';
    private const MODULE = 'employees';

    public function get(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid employee ID.'], 400);
        }

        $employee = $this->service()->get($id, $this->scopeEmployeeIdsForModule('employees'));

        if ($employee === null) {
            wp_send_json_error(['message' => 'Employee not found.'], 404);
        }

        wp_send_json_success([
            'employee' => EmployeesPresenter::employeePayload($employee),
            'user_accounts' => EmployeesPresenter::loadUserAccountOptions($id),
        ]);
    }

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $raw = $this->readInputFromPost();
            $raw['photo'] = $this->resolvePhotoPath($raw['photo'] ?? null);

            $input = $this->service()->inputFromArray($raw);

            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule('employees');

            if ($id > 0) {
                $employee = $this->service()->update($id, $input, $userId, $scopeEmployeeIds);
            } else {
                $employee = $this->service()->create($input, $userId);
            }

            wp_send_json_success([
                'employee' => EmployeesPresenter::employeePayload($employee),
                'message' => $id > 0
                    ? __('Employee updated successfully.', 'mf-staffcore-lite')
                    : __('Employee created successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            $this->sendJsonErrorWithPlanUpgrade($exception, 422);
        } catch (RuntimeException $exception) {
            $this->sendJsonErrorWithPlanUpgrade($exception, 500);
        }
    }

    public function delete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid employee ID.'], 400);
        }

        try {
            $this->service()->delete($id, $this->scopeEmployeeIdsForModule('employees'));

            wp_send_json_success([
                'message' => __('Employee deleted successfully.', 'mf-staffcore-lite'),
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
            $result = $this->service()->bulkDelete(
                $ids,
                get_current_user_id(),
                $this->scopeEmployeeIdsForModule('employees'),
            );
            $this->sendBulkDeleteResponse(
                $result,
                __('employee', 'mf-staffcore-lite'),
                __('employees', 'mf-staffcore-lite'),
            );
        } catch (ValidationException | RuntimeException $exception) {
            $this->handleBulkDeleteFailure($exception);
        }
    }

    public function removePhoto(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid employee ID.'], 400);
        }

        try {
            $employee = $this->service()->removePhoto(
                $id,
                get_current_user_id(),
                $this->scopeEmployeeIdsForModule('employees'),
            );

            wp_send_json_success([
                'employee' => EmployeesPresenter::employeePayload($employee),
                'message' => __('Employee photo removed.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePhotoPath(?string $existing): ?string
    {
        if (! isset($_FILES['photo']) || ! is_array($_FILES['photo'])) {
            return $existing !== null && $existing !== '' ? $existing : null;
        }

        $file = $_FILES['photo'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing !== null && $existing !== '' ? $existing : null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ValidationException(esc_html('Photo upload failed.'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['error'])) {
            throw new ValidationException( esc_html( (string) $upload['error'] ) );
        }

        return (string) ($upload['url'] ?? $existing);
    }

    /**
     * @return array<string, mixed>
     */
    private function readInputFromPost(): array
    {
        return [
            'code' => isset($_POST['code'])
                ? sanitize_text_field(wp_unslash((string) $_POST['code']))
                : '',
            'department_id' => isset($_POST['department_id'])
                ? (int) $_POST['department_id']
                : 0,
            'position_id' => isset($_POST['position_id'])
                ? (int) $_POST['position_id']
                : 0,
            'first_name' => isset($_POST['first_name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['first_name']))
                : '',
            'last_name' => isset($_POST['last_name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['last_name']))
                : '',
            'email' => isset($_POST['email'])
                ? sanitize_email(wp_unslash((string) $_POST['email']))
                : '',
            'phone' => isset($_POST['phone'])
                ? sanitize_text_field(wp_unslash((string) $_POST['phone']))
                : '',
            'user_id' => isset($_POST['user_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['user_id']))
                : '',
            'birth_date' => isset($_POST['birth_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['birth_date']))
                : '',
            'hire_date' => isset($_POST['hire_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['hire_date']))
                : '',
            'employment_status' => isset($_POST['employment_status'])
                ? sanitize_key(wp_unslash((string) $_POST['employment_status']))
                : 'ACTIVE',
            'employment_type' => isset($_POST['employment_type'])
                ? sanitize_key(wp_unslash((string) $_POST['employment_type']))
                : '',
            'basic_salary' => isset($_POST['basic_salary'])
                ? sanitize_text_field(wp_unslash((string) $_POST['basic_salary']))
                : '',
            'annual_leave' => isset($_POST['annual_leave'])
                ? sanitize_text_field(wp_unslash((string) $_POST['annual_leave']))
                : '0',
            'address' => isset($_POST['address'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['address']))
                : '',
            'city' => isset($_POST['city'])
                ? sanitize_text_field(wp_unslash((string) $_POST['city']))
                : '',
            'province' => isset($_POST['province'])
                ? sanitize_text_field(wp_unslash((string) $_POST['province']))
                : '',
            'country' => isset($_POST['country'])
                ? sanitize_text_field(wp_unslash((string) $_POST['country']))
                : '',
            'zip_code' => isset($_POST['zip_code'])
                ? sanitize_text_field(wp_unslash((string) $_POST['zip_code']))
                : '',
            'photo' => isset($_POST['photo_current'])
                ? esc_url_raw(wp_unslash((string) $_POST['photo_current']))
                : '',
            'notes' => isset($_POST['notes'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['notes']))
                : '',
            'allowances' => self::sanitizeAllowancesPayload(
                self::readAllowancesRawFromPost(),
            ),
        ];
    }

    private static function readAllowancesRawFromPost(): string
    {
        if (! isset($_POST['allowances'])) {
            return '[]';
        }

        $raw = wp_unslash($_POST['allowances']);

        return is_string($raw) ? $raw : '[]';
    }

    /**
     * Decode and sanitize the allowances JSON before it reaches the service layer.
     *
     * @return list<array{name: string, amount: float}>
     */
    private static function sanitizeAllowancesPayload(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        $allowances = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = sanitize_text_field(trim((string) ($item['name'] ?? '')));

            if ($name === '') {
                continue;
            }

            $amount = (float) ($item['amount'] ?? 0);

            if ($amount < 0) {
                continue;
            }

            $allowances[] = [
                'name' => $name,
                'amount' => $amount,
            ];
        }

        return $allowances;
    }

    private function service(): EmployeeService
    {
        return ServiceContainer::instance()->employees();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}


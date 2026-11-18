<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Payroll\Services\PayrollService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PayrollPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class PayrollAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;
    use StaffCoreEmployeeScopedAjax;

    private const NONCE_ACTION = 'mf_staffcore_payroll';
    private const MODULE = 'payroll';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());
            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule(self::MODULE);

            if ($id > 0) {
                $payroll = $this->service()->update($id, $input, $userId, $scopeEmployeeIds);
            } else {
                $payroll = $this->service()->create($input, $userId, $scopeEmployeeIds);
            }

            wp_send_json_success([
                'payroll' => PayrollPresenter::payrollPayload($payroll),
                'message' => $id > 0
                    ? __('Payroll record updated successfully.', 'mf-staffcore-lite')
                    : __('Payroll record created successfully.', 'mf-staffcore-lite'),
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
            wp_send_json_error(['message' => 'Invalid payroll record ID.'], 400);
        }

        try {
            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule(self::MODULE);
            $this->service()->delete($id, $scopeEmployeeIds);

            wp_send_json_success([
                'message' => __('Payroll record deleted successfully.', 'mf-staffcore-lite'),
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
            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule(self::MODULE);
            $result = $this->service()->bulkDelete($ids, get_current_user_id(), $scopeEmployeeIds);
            $this->sendBulkDeleteResponse(
                $result,
                __('payroll record', 'mf-staffcore-lite'),
                __('payroll records', 'mf-staffcore-lite'),
            );
        } catch (ValidationException | RuntimeException $exception) {
            $this->handleBulkDeleteFailure($exception);
        }
    }

    public function prefill(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
        $year = isset($_POST['payroll_year']) ? (int) $_POST['payroll_year'] : 0;
        $month = isset($_POST['payroll_month']) ? (int) $_POST['payroll_month'] : 0;

        try {
            $scopeEmployeeIds = $this->scopeEmployeeIdsForModule(self::MODULE);
            $data = $this->service()->prefill($employeeId, $year, $month, $scopeEmployeeIds);

            wp_send_json_success(['prefill' => $data]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readInputFromPost(): array
    {
        $fields = [
            'employee_id',
            'payroll_month',
            'payroll_year',
            'basic_salary',
            'allowances',
            'overtime_amount',
            'bonus',
            'commission',
            'tax',
            'insurance',
            'advance_deduction',
            'other_deductions',
            'working_days',
            'present_days',
            'absent_days',
            'leave_days',
            'overtime_hours',
            'payment_status',
            'payment_date',
            'payment_method',
            'payment_reference',
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($field === 'notes') {
                continue;
            }

            $data[$field] = isset($_POST[$field])
                ? sanitize_text_field(wp_unslash((string) $_POST[$field]))
                : '';
        }

        $data['notes'] = isset($_POST['notes'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['notes']))
            : '';

        if (isset($_POST['payment_status'])) {
            $data['payment_status'] = sanitize_key(wp_unslash((string) $_POST['payment_status']));
        }

        if (isset($_POST['payment_method'])) {
            $data['payment_method'] = sanitize_key(wp_unslash((string) $_POST['payment_method']));
        }

        return $data;
    }

    private function service(): PayrollService
    {
        return ServiceContainer::instance()->payroll();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

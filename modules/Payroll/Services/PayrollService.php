<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Payroll\Services;

use DateTimeImmutable;
use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\EmployeeScopeResolver;
use MF\StaffCore\Modules\Payroll\DTO\Payroll;
use MF\StaffCore\Modules\Payroll\DTO\PayrollInput;
use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Modules\Payroll\Repositories\PayrollRepository;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Navigation\PanelActionUrl;
use MF\StaffCore\Notification\EventCatalog;
use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Notification\NotificationDispatcher;
use MF\StaffCore\Notification\StaffCoreEvent;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class PayrollService
{
    private const TEXT_MAX = 65535;
    private const REFERENCE_MAX = 255;
    private const MIN_YEAR = 2000;
    private const MAX_YEAR = 2100;

    public function __construct(
        private readonly PayrollRepository $repository,
        private readonly NotificationDispatcher $notificationDispatcher,
        private readonly EmployeeScopeResolver $employeeScope,
    ) {
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return PaginatedList<Payroll>
     */
    public function listPage(
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?array $scopeEmployeeIds = null,
        ?PayrollListFilter $filter = null,
    ): PaginatedList
    {
        return $this->repository->findPage($page, $perPage, $scopeEmployeeIds, $filter);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function findById(int $id, ?array $scopeEmployeeIds = null): ?Payroll
    {
        return $this->repository->findById($id, $scopeEmployeeIds);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return list<array{id: int, label: string}>
     */
    public function employeeOptions(?array $scopeEmployeeIds = null): array
    {
        return $this->repository->findEmployeeOptions($scopeEmployeeIds);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return array{
     *   basic_salary: float,
     *   allowances: float,
     *   working_days: int,
     *   present_days: int,
     *   absent_days: int,
     *   leave_days: int
     * }
     */
    public function prefill(int $employeeId, int $year, int $month, ?array $scopeEmployeeIds = null): array
    {
        $employeeId = $this->normalizeInputEmployeeId($employeeId, $scopeEmployeeIds);

        if ($employeeId <= 0) {
            throw new ValidationException(esc_html('Employee is required.'));
        }

        if (! $this->repository->employeeExists($employeeId)) {
            throw new ValidationException(esc_html('Selected employee does not exist.'));
        }

        $this->assertPeriod($year, $month);

        return $this->repository->findPrefillData($employeeId, $year, $month);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function create(PayrollInput $input, int $userId, ?array $scopeEmployeeIds = null): Payroll
    {
        $input = $this->applyScope($input, $scopeEmployeeIds);
        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->employeePeriodExists($input->employeeId, $input->payrollYear, $input->payrollMonth)) {
            throw new ValidationException(esc_html('A payroll record already exists for this employee in the selected period.'));
        }

        $created = $this->repository->create($input, $userId);
        $this->dispatchCreated($created, $userId);

        return $created;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function update(int $id, PayrollInput $input, int $userId, ?array $scopeEmployeeIds = null): Payroll
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Payroll record not found.'));
        }

        $input = $this->applyScope($input, $scopeEmployeeIds);
        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->employeePeriodExists(
            $input->employeeId,
            $input->payrollYear,
            $input->payrollMonth,
            $id,
        )) {
            throw new ValidationException(esc_html('A payroll record already exists for this employee in the selected period.'));
        }

        $updated = $this->repository->update($id, $input, $userId);

        if ($existing->paymentStatus !== $updated->paymentStatus) {
            if ($updated->paymentStatus === Payroll::STATUS_PAID) {
                $this->dispatchPaid($updated, $userId);
            }

            if ($updated->paymentStatus === Payroll::STATUS_ON_HOLD) {
                $this->dispatchOnHold($updated, $userId);
            }

            if ($updated->paymentStatus === Payroll::STATUS_PROCESSED) {
                $this->dispatchProcessed($updated, $userId);
            }
        }

        return $updated;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function delete(int $id, ?array $scopeEmployeeIds = null): void
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Payroll record not found.'));
        }

        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     * @param list<int>|null $scopeEmployeeIds
     */
    public function bulkDelete(array $ids, int $actorUserId, ?array $scopeEmployeeIds = null): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute(
            $ids,
            fn (int $id) => $this->delete($id, $scopeEmployeeIds),
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): PayrollInput
    {
        return new PayrollInput(
            employeeId: isset($raw['employee_id']) && $raw['employee_id'] !== ''
                ? (int) $raw['employee_id']
                : 0,
            payrollMonth: isset($raw['payroll_month']) && $raw['payroll_month'] !== ''
                ? (int) $raw['payroll_month']
                : 0,
            payrollYear: isset($raw['payroll_year']) && $raw['payroll_year'] !== ''
                ? (int) $raw['payroll_year']
                : 0,
            basicSalary: $this->parseAmount($raw['basic_salary'] ?? 0),
            allowances: $this->parseAmount($raw['allowances'] ?? 0),
            overtimeAmount: $this->parseAmount($raw['overtime_amount'] ?? 0),
            bonus: $this->parseAmount($raw['bonus'] ?? 0),
            commission: $this->parseAmount($raw['commission'] ?? 0),
            tax: $this->parseAmount($raw['tax'] ?? 0),
            insurance: $this->parseAmount($raw['insurance'] ?? 0),
            advanceDeduction: $this->parseAmount($raw['advance_deduction'] ?? 0),
            otherDeductions: $this->parseAmount($raw['other_deductions'] ?? 0),
            workingDays: isset($raw['working_days']) && $raw['working_days'] !== ''
                ? (int) $raw['working_days']
                : 0,
            presentDays: isset($raw['present_days']) && $raw['present_days'] !== ''
                ? (int) $raw['present_days']
                : 0,
            absentDays: isset($raw['absent_days']) && $raw['absent_days'] !== ''
                ? (int) $raw['absent_days']
                : 0,
            leaveDays: isset($raw['leave_days']) && $raw['leave_days'] !== ''
                ? (int) $raw['leave_days']
                : 0,
            overtimeHours: $this->parseAmount($raw['overtime_hours'] ?? 0),
            paymentStatus: (string) ($raw['payment_status'] ?? Payroll::STATUS_PENDING),
            paymentDate: isset($raw['payment_date']) ? (string) $raw['payment_date'] : null,
            paymentMethod: isset($raw['payment_method']) ? (string) $raw['payment_method'] : null,
            paymentReference: isset($raw['payment_reference']) ? (string) $raw['payment_reference'] : null,
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
        );
    }

    private function normalize(PayrollInput $input): PayrollInput
    {
        return new PayrollInput(
            employeeId: $input->employeeId,
            payrollMonth: $input->payrollMonth,
            payrollYear: $input->payrollYear,
            basicSalary: max(0, $input->basicSalary),
            allowances: max(0, $input->allowances),
            overtimeAmount: max(0, $input->overtimeAmount),
            bonus: max(0, $input->bonus),
            commission: max(0, $input->commission),
            tax: max(0, $input->tax),
            insurance: max(0, $input->insurance),
            advanceDeduction: max(0, $input->advanceDeduction),
            otherDeductions: max(0, $input->otherDeductions),
            workingDays: max(0, $input->workingDays),
            presentDays: max(0, $input->presentDays),
            absentDays: max(0, $input->absentDays),
            leaveDays: max(0, $input->leaveDays),
            overtimeHours: max(0, $input->overtimeHours),
            paymentStatus: strtoupper(trim($input->paymentStatus)),
            paymentDate: $this->trimNullable($input->paymentDate),
            paymentMethod: $this->normalizePaymentMethod($input->paymentMethod),
            paymentReference: $this->trimNullable($input->paymentReference),
            notes: $this->trimNullable($input->notes),
        );
    }

    private function normalizePaymentMethod(?string $value): ?string
    {
        $trimmed = $this->trimNullable($value);

        if ($trimmed === null) {
            return null;
        }

        return strtoupper($trimmed);
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return max(0, (float) $value);
    }

    private function assert(PayrollInput $input): void
    {
        if ($input->employeeId <= 0) {
            throw new ValidationException(esc_html('Employee is required.'));
        }

        if (! $this->repository->employeeExists($input->employeeId)) {
            throw new ValidationException(esc_html('Selected employee does not exist.'));
        }

        $this->assertPeriod($input->payrollYear, $input->payrollMonth);

        if ($input->workingDays <= 0) {
            throw new ValidationException(esc_html('Working days must be at least 1.'));
        }

        if ($input->presentDays < 0 || $input->absentDays < 0 || $input->leaveDays < 0) {
            throw new ValidationException(esc_html('Day counts cannot be negative.'));
        }

        $statuses = [
            Payroll::STATUS_PENDING,
            Payroll::STATUS_PROCESSED,
            Payroll::STATUS_PAID,
            Payroll::STATUS_ON_HOLD,
        ];

        if (! in_array($input->paymentStatus, $statuses, true)) {
            throw new ValidationException(esc_html('Invalid payment status.'));
        }

        $methods = [
            Payroll::METHOD_CASH,
            Payroll::METHOD_BANK,
            Payroll::METHOD_CHEQUE,
        ];

        if ($input->paymentMethod !== null && ! in_array($input->paymentMethod, $methods, true)) {
            throw new ValidationException(esc_html('Invalid payment method.'));
        }

        if ($input->paymentDate !== null && ! $this->isValidDate($input->paymentDate)) {
            throw new ValidationException(esc_html('Invalid payment date.'));
        }

        if ($input->paymentReference !== null && strlen($input->paymentReference) > self::REFERENCE_MAX) {
            throw new ValidationException(esc_html('Payment reference is too long.'));
        }

        if ($input->notes !== null && strlen($input->notes) > self::TEXT_MAX) {
            throw new ValidationException(esc_html('Notes are too long.'));
        }
    }

    private function assertPeriod(int $year, int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new ValidationException(esc_html('Payroll month must be between 1 and 12.'));
        }

        if ($year < self::MIN_YEAR || $year > self::MAX_YEAR) {
            throw new ValidationException(esc_html('Invalid payroll year.'));
        }
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function applyScope(PayrollInput $input, ?array $scopeEmployeeIds): PayrollInput
    {
        if ($scopeEmployeeIds === null) {
            return $input;
        }

        try {
            $employeeId = $this->employeeScope->normalizeInputEmployeeId($input->employeeId, $scopeEmployeeIds);
        } catch (AuthorizationException $exception) {
            throw new ValidationException( esc_html( $exception->getMessage() ) );
        }

        return new PayrollInput(
            employeeId: $employeeId,
            payrollMonth: $input->payrollMonth,
            payrollYear: $input->payrollYear,
            basicSalary: $input->basicSalary,
            allowances: $input->allowances,
            overtimeAmount: $input->overtimeAmount,
            bonus: $input->bonus,
            commission: $input->commission,
            tax: $input->tax,
            insurance: $input->insurance,
            advanceDeduction: $input->advanceDeduction,
            otherDeductions: $input->otherDeductions,
            workingDays: $input->workingDays,
            presentDays: $input->presentDays,
            absentDays: $input->absentDays,
            leaveDays: $input->leaveDays,
            overtimeHours: $input->overtimeHours,
            paymentStatus: $input->paymentStatus,
            paymentDate: $input->paymentDate,
            paymentMethod: $input->paymentMethod,
            paymentReference: $input->paymentReference,
            notes: $input->notes,
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function normalizeInputEmployeeId(int $employeeId, ?array $scopeEmployeeIds): int
    {
        if ($scopeEmployeeIds === null) {
            return $employeeId;
        }

        try {
            return $this->employeeScope->normalizeInputEmployeeId($employeeId, $scopeEmployeeIds);
        } catch (AuthorizationException $exception) {
            throw new ValidationException( esc_html( $exception->getMessage() ) );
        }
    }

    private function payrollPanelActionUrl(): string
    {
        return PanelActionUrl::relative(new AdminRoute('hr-management', 'payroll'));
    }

    private function employeePayslipActionUrl(int $payrollId): string
    {
        return PanelActionUrl::relative(
            new AdminRoute('hr-management', 'my-payslips'),
            [
                'sc_view' => 'payslip',
                'payroll_id' => $payrollId,
            ],
        );
    }

    private function payrollPeriodBody(Payroll $payroll): string
    {
        return sprintf(
            'Payroll period %d-%02d.',
            $payroll->payrollYear,
            $payroll->payrollMonth,
        );
    }

    private function dispatchCreated(Payroll $payroll, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::PAYROLL_CREATED,
                title: 'Payroll record created',
                body: $this->payrollPeriodBody($payroll),
                entityType: 'payroll',
                entityId: $payroll->id,
                actionUrl: $this->employeePayslipActionUrl($payroll->id),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $payroll->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchPaid(Payroll $payroll, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::PAYROLL_PAYMENT_STATUS_PAID,
                title: 'Payroll marked as paid',
                body: sprintf(
                    'Payroll period %d-%02d is now paid.',
                    $payroll->payrollYear,
                    $payroll->payrollMonth,
                ),
                entityType: 'payroll',
                entityId: $payroll->id,
                actionUrl: $this->employeePayslipActionUrl($payroll->id),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $payroll->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchOnHold(Payroll $payroll, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::PAYROLL_PAYMENT_STATUS_ON_HOLD,
                title: 'Payroll placed on hold',
                body: sprintf(
                    'Payroll period %d-%02d has been placed on hold.',
                    $payroll->payrollYear,
                    $payroll->payrollMonth,
                ),
                entityType: 'payroll',
                entityId: $payroll->id,
                actionUrl: $this->payrollPanelActionUrl(),
                priority: Notification::PRIORITY_HIGH,
                recipientCodes: [
                    StaffCoreEvent::RECIPIENT_EMPLOYEE,
                    StaffCoreEvent::RECIPIENT_HR,
                ],
                context: ['employee_id' => $payroll->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchProcessed(Payroll $payroll, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::PAYROLL_PAYMENT_STATUS_PROCESSED,
                title: 'Payroll marked as processed',
                body: sprintf(
                    'Payroll period %d-%02d has been processed.',
                    $payroll->payrollYear,
                    $payroll->payrollMonth,
                ),
                entityType: 'payroll',
                entityId: $payroll->id,
                actionUrl: $this->employeePayslipActionUrl($payroll->id),
                priority: Notification::PRIORITY_LOW,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $payroll->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }
}


<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\LeaveRequests\Services;

use DateTimeImmutable;
use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\EmployeeScopeResolver;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestInput;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestListFilter;
use MF\StaffCore\Modules\LeaveRequests\Repositories\LeaveRequestRepository;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Navigation\PanelActionUrl;
use MF\StaffCore\Notification\EventCatalog;
use MF\StaffCore\Notification\NotificationDispatcher;
use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Notification\StaffCoreEvent;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class LeaveRequestService
{
    private const LEAVE_TYPE_MAX = 100;
    private const TEXT_MAX = 65535;

    public function __construct(
        private readonly LeaveRequestRepository $repository,
        private readonly NotificationDispatcher $notificationDispatcher,
        private readonly EmployeeScopeResolver $employeeScope,
    ) {
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return PaginatedList<LeaveRequest>
     */
    public function listPage(
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?array $scopeEmployeeIds = null,
        ?LeaveRequestListFilter $filter = null,
    ): PaginatedList {
        return $this->repository->findPage($page, $perPage, $scopeEmployeeIds, $filter);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function findById(int $id, ?array $scopeEmployeeIds = null): ?LeaveRequest
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
     */
    public function create(LeaveRequestInput $input, int $userId, ?array $scopeEmployeeIds = null): LeaveRequest
    {
        $input = $this->applyScope($input, $scopeEmployeeIds);
        $input = $this->applySelfServiceRules($input, $scopeEmployeeIds);
        $input = $this->normalize($input);
        $this->assert($input);

        $created = $this->repository->create($input, $userId);

        if ($created->status === LeaveRequest::STATUS_PENDING) {
            $this->dispatchSubmitted($created, $userId);
        }

        return $created;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function update(int $id, LeaveRequestInput $input, int $userId, ?array $scopeEmployeeIds = null): LeaveRequest
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Leave request not found.'));
        }

        $input = $this->applyScope($input, $scopeEmployeeIds);
        $input = $this->applySelfServiceRules($input, $scopeEmployeeIds, $existing);
        $input = $this->normalize($input);
        $this->assert($input, $id);

        $updated = $this->repository->update($id, $input, $userId);

        if ($existing->status !== $updated->status) {
            if ($updated->status === LeaveRequest::STATUS_PENDING) {
                $this->dispatchSubmitted($updated, $userId);
            }

            if ($updated->status === LeaveRequest::STATUS_APPROVED) {
                $this->dispatchApproved($updated, $userId);
            }

            if ($updated->status === LeaveRequest::STATUS_REJECTED) {
                $this->dispatchRejected($updated, $userId);
            }

            if ($updated->status === LeaveRequest::STATUS_CANCELLED) {
                $this->dispatchCancelled($updated, $userId);
            }
        }

        return $updated;
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function approve(int $id, int $userId, ?array $scopeEmployeeIds = null): LeaveRequest
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Leave request not found.'));
        }

        if ($existing->status !== LeaveRequest::STATUS_PENDING) {
            throw new ValidationException(esc_html('Only pending leave requests can be approved.'));
        }

        $input = new LeaveRequestInput(
            employeeId: $existing->employeeId,
            leaveType: $existing->leaveType,
            startDate: $existing->startDate,
            endDate: $existing->endDate,
            totalDays: $existing->totalDays,
            reason: $existing->reason,
            notes: $existing->notes,
            status: LeaveRequest::STATUS_APPROVED,
        );

        return $this->update($id, $input, $userId, $scopeEmployeeIds);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function delete(int $id, ?array $scopeEmployeeIds = null): void
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Leave request not found.'));
        }

        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     */
    /**
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
     * Self-service: force PENDING status; edits allowed only on pending rows.
     *
     * @param list<int>|null $scopeEmployeeIds
     */
    private function applySelfServiceRules(
        LeaveRequestInput $input,
        ?array $scopeEmployeeIds,
        ?LeaveRequest $existing = null,
    ): LeaveRequestInput {
        if (! $this->employeeScope->isSingleEmployeeScope($scopeEmployeeIds)) {
            return $input;
        }

        if ($existing !== null && $existing->status !== LeaveRequest::STATUS_PENDING) {
            throw new ValidationException(esc_html('Only pending leave requests can be edited.'));
        }

        return new LeaveRequestInput(
            employeeId: $input->employeeId,
            leaveType: $input->leaveType,
            startDate: $input->startDate,
            endDate: $input->endDate,
            totalDays: $input->totalDays,
            reason: $input->reason,
            notes: $input->notes,
            status: LeaveRequest::STATUS_PENDING,
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function applyScope(LeaveRequestInput $input, ?array $scopeEmployeeIds): LeaveRequestInput
    {
        if ($scopeEmployeeIds === null) {
            return $input;
        }

        try {
            $employeeId = $this->employeeScope->normalizeInputEmployeeId($input->employeeId, $scopeEmployeeIds);
        } catch (AuthorizationException $exception) {
            throw new ValidationException( esc_html( $exception->getMessage() ) );
        }

        return new LeaveRequestInput(
            employeeId: $employeeId,
            leaveType: $input->leaveType,
            startDate: $input->startDate,
            endDate: $input->endDate,
            totalDays: $input->totalDays,
            reason: $input->reason,
            notes: $input->notes,
            status: $input->status,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): LeaveRequestInput
    {
        $employeeId = isset($raw['employee_id']) && $raw['employee_id'] !== ''
            ? (int) $raw['employee_id']
            : 0;

        $totalDays = isset($raw['total_days']) && $raw['total_days'] !== ''
            ? (int) $raw['total_days']
            : 0;

        return new LeaveRequestInput(
            employeeId: $employeeId,
            leaveType: (string) ($raw['leave_type'] ?? ''),
            startDate: (string) ($raw['start_date'] ?? ''),
            endDate: (string) ($raw['end_date'] ?? ''),
            totalDays: $totalDays,
            reason: isset($raw['reason']) ? (string) $raw['reason'] : null,
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
            status: (string) ($raw['status'] ?? LeaveRequest::STATUS_PENDING),
        );
    }

    private function normalize(LeaveRequestInput $input): LeaveRequestInput
    {
        $startDate = trim($input->startDate);
        $endDate = trim($input->endDate);
        $totalDays = $this->calculateTotalDays($startDate, $endDate);

        return new LeaveRequestInput(
            employeeId: $input->employeeId,
            leaveType: trim($input->leaveType),
            startDate: $startDate,
            endDate: $endDate,
            totalDays: $totalDays,
            reason: $this->trimNullable($input->reason),
            notes: $this->trimNullable($input->notes),
            status: strtoupper(trim($input->status)),
        );
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assert(LeaveRequestInput $input, ?int $excludeId = null): void
    {
        if ($input->employeeId <= 0) {
            throw new ValidationException(esc_html('Employee is required.'));
        }

        if (! $this->repository->employeeExists($input->employeeId)) {
            throw new ValidationException(esc_html('Selected employee does not exist.'));
        }

        if ($input->leaveType === '') {
            throw new ValidationException(esc_html('Leave type is required.'));
        }

        if (strlen($input->leaveType) > self::LEAVE_TYPE_MAX) {
            throw new ValidationException(esc_html('Leave type must be at most ' . self::LEAVE_TYPE_MAX . ' characters.'));
        }

        if (! in_array($input->leaveType, LeaveRequest::leaveTypes(), true)) {
            throw new ValidationException(esc_html('Invalid leave type.'));
        }

        if ($input->startDate === '' || $input->endDate === '') {
            throw new ValidationException(esc_html('Start and end dates are required.'));
        }

        if (! $this->isValidDate($input->startDate) || ! $this->isValidDate($input->endDate)) {
            throw new ValidationException(esc_html('Invalid date format.'));
        }

        if ($input->startDate > $input->endDate) {
            throw new ValidationException(esc_html('End date must be on or after start date.'));
        }

        if ($input->totalDays <= 0) {
            throw new ValidationException(esc_html('Total days must be at least 1.'));
        }

        $statuses = [
            LeaveRequest::STATUS_PENDING,
            LeaveRequest::STATUS_APPROVED,
            LeaveRequest::STATUS_REJECTED,
            LeaveRequest::STATUS_CANCELLED,
        ];

        if (! in_array($input->status, $statuses, true)) {
            throw new ValidationException(esc_html('Invalid leave request status.'));
        }

        if ($input->reason !== null && strlen($input->reason) > self::TEXT_MAX) {
            throw new ValidationException(esc_html('Reason is too long.'));
        }

        if ($input->notes !== null && strlen($input->notes) > self::TEXT_MAX) {
            throw new ValidationException(esc_html('Notes are too long.'));
        }

        if ($input->status === LeaveRequest::STATUS_APPROVED && $input->leaveType === LeaveRequest::TYPE_ANNUAL) {
            $this->assertAnnualLeaveBalance($input, $excludeId);
        }
    }

    private function assertAnnualLeaveBalance(LeaveRequestInput $input, ?int $excludeId): void
    {
        $year = (int) (new DateTimeImmutable($input->startDate))->format('Y');
        $annual = $this->repository->findEmployeeAnnualLeave($input->employeeId);
        $used = $this->repository->findApprovedLeaveDaysForYear($input->employeeId, $year, $excludeId);
        $remaining = max(0, $annual - $used);

        if ($input->totalDays > $remaining) {
            throw new ValidationException(esc_html(sprintf(
                    'Insufficient annual leave balance. Remaining: %d day(s), requested: %d day(s).',
                    $remaining,
                    $input->totalDays,
                )));
        }
    }

    private function calculateTotalDays(string $startDate, string $endDate): int
    {
        if ($startDate === '' || $endDate === '' || ! $this->isValidDate($startDate) || ! $this->isValidDate($endDate)) {
            return 0;
        }

        if ($startDate > $endDate) {
            return 0;
        }

        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        return (int) $start->diff($end)->days + 1;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function dispatchSubmitted(LeaveRequest $leaveRequest, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::LEAVE_REQUEST_SUBMITTED,
                title: 'Leave request submitted',
                body: $this->leaveBody($leaveRequest),
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'leave-requests')),
                priority: Notification::PRIORITY_HIGH,
                recipientCodes: [
                    StaffCoreEvent::RECIPIENT_HR,
                    StaffCoreEvent::RECIPIENT_MANAGER,
                ],
                context: ['employee_id' => $leaveRequest->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchApproved(LeaveRequest $leaveRequest, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::LEAVE_REQUEST_APPROVED,
                title: 'Leave request approved',
                body: $this->leaveBody($leaveRequest),
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'leave-requests')),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $leaveRequest->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchRejected(LeaveRequest $leaveRequest, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::LEAVE_REQUEST_REJECTED,
                title: 'Leave request rejected',
                body: $this->leaveBody($leaveRequest),
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'leave-requests')),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $leaveRequest->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchCancelled(LeaveRequest $leaveRequest, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::LEAVE_REQUEST_CANCELLED,
                title: 'Leave request cancelled',
                body: $this->leaveBody($leaveRequest),
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'leave-requests')),
                priority: Notification::PRIORITY_LOW,
                recipientCodes: [
                    StaffCoreEvent::RECIPIENT_HR,
                    StaffCoreEvent::RECIPIENT_MANAGER,
                ],
                context: ['employee_id' => $leaveRequest->employeeId],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function leaveBody(LeaveRequest $leaveRequest): string
    {
        return sprintf(
            '%s (%s) from %s to %s.',
            $leaveRequest->employeeName ?? ('Employee #' . $leaveRequest->employeeId),
            $leaveRequest->leaveType,
            $leaveRequest->startDate,
            $leaveRequest->endDate,
        );
    }
}


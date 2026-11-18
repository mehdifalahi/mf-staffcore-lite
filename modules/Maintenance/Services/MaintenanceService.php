<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Maintenance\Services;

use DateTimeImmutable;
use MF\StaffCore\Modules\Maintenance\DTO\Maintenance;
use MF\StaffCore\Modules\Maintenance\DTO\MaintenanceInput;
use MF\StaffCore\Modules\Maintenance\DTO\MaintenanceListFilter;
use MF\StaffCore\Modules\Maintenance\Repositories\MaintenanceRepository;
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

final class MaintenanceService
{
    private const PERFORMED_BY_MAX = 255;
    private const NOTES_MAX = 65535;

    public function __construct(
        private readonly MaintenanceRepository $repository,
        private readonly NotificationDispatcher $notificationDispatcher,
    ) {
    }

    /**
     * @return PaginatedList<Maintenance>
     */
    public function listPage(
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?MaintenanceListFilter $filter = null,
    ): PaginatedList
    {
        return $this->repository->findPage($page, $perPage, $filter);
    }

    public function findById(int $id): ?Maintenance
    {
        return $this->repository->findById($id);
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function assetOptions(): array
    {
        return $this->repository->findAssetOptions();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function employeeOptions(): array
    {
        return $this->repository->findEmployeeOptions();
    }

    public function create(MaintenanceInput $input, int $userId): Maintenance
    {
        $input = $this->normalize($input);
        $this->assert($input);

        $created = $this->repository->create($input, $userId);

        if ($created->nextServiceDate !== null && $created->nextServiceDate !== '') {
            $this->dispatchScheduled($created, $userId);
        }

        return $created;
    }

    public function update(int $id, MaintenanceInput $input, int $userId): Maintenance
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Maintenance record not found.'));
        }

        $input = $this->normalize($input);
        $this->assert($input);

        return $this->repository->update($id, $input, $userId);
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Maintenance record not found.'));
        }

        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     */
    public function bulkDelete(array $ids, int $actorUserId): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute($ids, fn (int $id) => $this->delete($id));
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): MaintenanceInput
    {
        $assetId = isset($raw['asset_id']) && $raw['asset_id'] !== ''
            ? (int) $raw['asset_id']
            : 0;

        $cost = isset($raw['cost']) && $raw['cost'] !== ''
            ? (float) $raw['cost']
            : null;

        $performedByEmployeeId = isset($raw['performed_by_employee_id']) && $raw['performed_by_employee_id'] !== ''
            ? (int) $raw['performed_by_employee_id']
            : null;

        return new MaintenanceInput(
            assetId: $assetId,
            maintenanceDate: (string) ($raw['maintenance_date'] ?? ''),
            type: (string) ($raw['type'] ?? Maintenance::TYPE_SERVICE),
            cost: $cost,
            performedByEmployeeId: $performedByEmployeeId,
            performedBy: null,
            nextServiceDate: isset($raw['next_service_date']) ? (string) $raw['next_service_date'] : null,
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
        );
    }

    private function normalize(MaintenanceInput $input): MaintenanceInput
    {
        $cost = $input->cost !== null ? max(0, $input->cost) : null;
        $employeeId = $input->performedByEmployeeId !== null && $input->performedByEmployeeId > 0
            ? $input->performedByEmployeeId
            : null;
        $performedBy = null;

        if ($employeeId !== null) {
            $performedBy = $this->repository->findEmployeeLabel($employeeId);
        }

        return new MaintenanceInput(
            assetId: $input->assetId,
            maintenanceDate: trim($input->maintenanceDate),
            type: strtoupper(trim($input->type)),
            cost: $cost,
            performedByEmployeeId: $employeeId,
            performedBy: $this->trimNullable($performedBy),
            nextServiceDate: $this->trimNullable($input->nextServiceDate),
            notes: $this->trimNullable($input->notes),
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

    private function assert(MaintenanceInput $input): void
    {
        if ($input->assetId <= 0) {
            throw new ValidationException(esc_html('Asset is required.'));
        }

        if (! $this->repository->assetExists($input->assetId)) {
            throw new ValidationException(esc_html('Selected asset does not exist.'));
        }

        if ($input->maintenanceDate === '') {
            throw new ValidationException(esc_html('Maintenance date is required.'));
        }

        if (! $this->isValidDate($input->maintenanceDate)) {
            throw new ValidationException(esc_html('Invalid maintenance date.'));
        }

        if (! in_array($input->type, Maintenance::types(), true)) {
            throw new ValidationException(esc_html('Invalid maintenance type.'));
        }

        if ($input->performedByEmployeeId !== null) {
            if (! $this->repository->employeeExists($input->performedByEmployeeId)) {
                throw new ValidationException(esc_html('Selected employee does not exist.'));
            }

            if ($input->performedBy === null || $input->performedBy === '') {
                throw new ValidationException(esc_html('Could not resolve employee name for performed by.'));
            }
        }

        if ($input->performedBy !== null && strlen($input->performedBy) > self::PERFORMED_BY_MAX) {
            throw new ValidationException(esc_html('Performed by must be at most ' . self::PERFORMED_BY_MAX . ' characters.'));
        }

        if ($input->nextServiceDate !== null) {
            if (! $this->isValidDate($input->nextServiceDate)) {
                throw new ValidationException(esc_html('Invalid next service date.'));
            }

            if ($input->nextServiceDate < $input->maintenanceDate) {
                throw new ValidationException(esc_html('Next service date must be on or after maintenance date.'));
            }
        }

        if ($input->notes !== null && strlen($input->notes) > self::NOTES_MAX) {
            throw new ValidationException(esc_html('Notes are too long.'));
        }
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function dispatchScheduled(Maintenance $maintenance, int $actorPlatformUserId): void
    {
        $assetLabel = $maintenance->assetName !== null && $maintenance->assetName !== ''
            ? $maintenance->assetName
            : ('Asset #' . $maintenance->assetId);

        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::MAINTENANCE_SCHEDULED,
                title: 'Maintenance scheduled',
                body: sprintf(
                    '%s — next service on %s.',
                    $assetLabel,
                    $maintenance->nextServiceDate,
                ),
                entityType: 'maintenance',
                entityId: $maintenance->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('assets', 'maintenance')),
                priority: Notification::PRIORITY_LOW,
                recipientCodes: [StaffCoreEvent::RECIPIENT_HR],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }
}


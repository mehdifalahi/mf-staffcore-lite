<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Assets\Services;

use MF\StaffCore\Modules\AssetCategories\Repositories\AssetCategoryRepository;
use MF\StaffCore\Modules\Assets\DTO\Asset;
use MF\StaffCore\Modules\Assets\DTO\AssetInput;
use MF\StaffCore\Modules\Assets\DTO\AssetListFilter;
use MF\StaffCore\Modules\Assets\Repositories\AssetRepository;
use MF\StaffCore\Modules\Suppliers\Repositories\SupplierRepository;
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

final class AssetService
{
    private const CODE_MAX = 100;
    private const NAME_MAX = 255;
    private const SERIAL_MAX = 255;
    private const LOCATION_MAX = 255;
    private const IMAGE_MAX = 500;

    public function __construct(
        private readonly AssetRepository $repository,
        private readonly AssetCategoryRepository $categoryRepository,
        private readonly SupplierRepository $supplierRepository,
        private readonly NotificationDispatcher $notificationDispatcher,
    ) {
    }

    /**
     * @return PaginatedList<Asset>
     */
    public function listPage(
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?AssetListFilter $filter = null,
    ): PaginatedList
    {
        return $this->repository->findPage($page, $perPage, $filter);
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function employeeOptions(): array
    {
        return $this->repository->findEmployeeOptions();
    }

    public function create(AssetInput $input, int $userId): Asset
    {
        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->codeExists($input->code)) {
            throw new ValidationException(esc_html('An asset with this code already exists.'));
        }

        $created = $this->repository->create($input, $userId);
        $this->dispatchAssetChanges(null, $created, $userId);

        return $created;
    }

    public function update(int $id, AssetInput $input, int $userId): Asset
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Asset not found.'));
        }

        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->codeExists($input->code, $id)) {
            throw new ValidationException(esc_html('An asset with this code already exists.'));
        }

        $updated = $this->repository->update($id, $input, $userId);
        $this->dispatchAssetChanges($existing, $updated, $userId);

        return $updated;
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Asset not found.'));
        }

        $this->deleteStoredImageFile($existing->image);
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

    public function removeImage(int $id, int $userId): Asset
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Asset not found.'));
        }

        $this->deleteStoredImageFile($existing->image);

        return $this->repository->clearImage($id, $userId);
    }

    private function deleteStoredImageFile(?string $imageUrl): void
    {
        if ($imageUrl === null || $imageUrl === '') {
            return;
        }

        $uploadDir = wp_upload_dir();
        $baseUrl = $uploadDir['baseurl'] ?? '';

        if ($baseUrl === '' || ! str_starts_with($imageUrl, $baseUrl)) {
            return;
        }

        $relative = ltrim(substr($imageUrl, strlen($baseUrl)), '/');
        $filePath = wp_normalize_path($uploadDir['basedir'] . '/' . $relative);

        if ($filePath !== '' && is_file($filePath)) {
            wp_delete_file($filePath);
        }
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): AssetInput
    {
        $assignedTo = isset($raw['assigned_to']) && $raw['assigned_to'] !== ''
            ? (int) $raw['assigned_to']
            : null;

        $supplierId = isset($raw['supplier_id']) && $raw['supplier_id'] !== ''
            ? (int) $raw['supplier_id']
            : null;

        return new AssetInput(
            code: (string) ($raw['code'] ?? ''),
            name: (string) ($raw['name'] ?? ''),
            categoryId: (int) ($raw['category_id'] ?? 0),
            purchaseDate: isset($raw['purchase_date']) ? (string) $raw['purchase_date'] : null,
            purchaseCost: isset($raw['purchase_cost']) ? (string) $raw['purchase_cost'] : null,
            currentValue: isset($raw['current_value']) ? (string) $raw['current_value'] : null,
            serialNumber: isset($raw['serial_number']) ? (string) $raw['serial_number'] : null,
            warrantyExpiry: isset($raw['warranty_expiry']) ? (string) $raw['warranty_expiry'] : null,
            status: (string) ($raw['status'] ?? Asset::STATUS_ACTIVE),
            assetCondition: (string) ($raw['condition'] ?? Asset::CONDITION_GOOD),
            assignedTo: $assignedTo,
            location: isset($raw['location']) ? (string) $raw['location'] : null,
            supplierId: $supplierId,
            image: isset($raw['image']) ? (string) $raw['image'] : null,
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
        );
    }

    private function normalize(AssetInput $input): AssetInput
    {
        return new AssetInput(
            code: strtoupper(trim($input->code)),
            name: trim($input->name),
            categoryId: $input->categoryId,
            purchaseDate: $this->normalizeDate($input->purchaseDate),
            purchaseCost: $this->normalizeDecimal($input->purchaseCost),
            currentValue: $this->normalizeDecimal($input->currentValue),
            serialNumber: $this->trimNullable($input->serialNumber),
            warrantyExpiry: $this->normalizeDate($input->warrantyExpiry),
            status: strtoupper(trim($input->status)),
            assetCondition: strtoupper(trim($input->assetCondition)),
            assignedTo: $input->assignedTo,
            location: $this->trimNullable($input->location),
            supplierId: $input->supplierId,
            image: $this->trimNullable($input->image),
            notes: $this->trimNullable($input->notes),
        );
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = $this->trimNullable($value);

        if ($value === null) {
            return null;
        }

        return $value;
    }

    private function normalizeDecimal(?string $value): ?string
    {
        $value = $this->trimNullable($value);

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            return $value;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assert(AssetInput $input): void
    {
        if ($input->code === '') {
            throw new ValidationException(esc_html('Asset code is required.'));
        }

        if ($input->name === '') {
            throw new ValidationException(esc_html('Asset name is required.'));
        }

        if ($input->categoryId <= 0) {
            throw new ValidationException(esc_html('Asset category is required.'));
        }

        $this->assertMaxLength('Asset code', $input->code, self::CODE_MAX);
        $this->assertMaxLength('Asset name', $input->name, self::NAME_MAX);
        $this->assertMaxLength('Serial number', $input->serialNumber, self::SERIAL_MAX);
        $this->assertMaxLength('Location', $input->location, self::LOCATION_MAX);
        $this->assertMaxLength('Image', $input->image, self::IMAGE_MAX);

        if ($this->categoryRepository->findById($input->categoryId) === null) {
            throw new ValidationException(esc_html('Selected category does not exist.'));
        }

        if ($input->supplierId !== null && $this->supplierRepository->findById($input->supplierId) === null) {
            throw new ValidationException(esc_html('Selected supplier does not exist.'));
        }

        if (
            ! in_array($input->status, [
                Asset::STATUS_ACTIVE,
                Asset::STATUS_INACTIVE,
                Asset::STATUS_UNDER_REPAIR,
                Asset::STATUS_DISPOSED,
            ], true)
        ) {
            throw new ValidationException(esc_html('Invalid asset status.'));
        }

        if (
            ! in_array($input->assetCondition, [
                Asset::CONDITION_EXCELLENT,
                Asset::CONDITION_GOOD,
                Asset::CONDITION_FAIR,
                Asset::CONDITION_POOR,
            ], true)
        ) {
            throw new ValidationException(esc_html('Invalid asset condition.'));
        }

        if ($input->purchaseCost !== null && ! is_numeric($input->purchaseCost)) {
            throw new ValidationException(esc_html('Purchase cost must be a number.'));
        }

        if ($input->currentValue !== null && ! is_numeric($input->currentValue)) {
            throw new ValidationException(esc_html('Current value must be a number.'));
        }
    }

    private function assertMaxLength(string $label, ?string $value, int $max): void
    {
        if ($value !== null && strlen($value) > $max) {
            throw new ValidationException( esc_html( $label . ' must be at most ' . $max . ' characters.' ) );
        }
    }

    private function assetLabel(Asset $asset): string
    {
        return sprintf('%s (%s)', $asset->name, $asset->code);
    }

    private function dispatchAssetChanges(?Asset $existing, Asset $updated, int $actorPlatformUserId): void
    {
        $previousAssignee = $existing?->assignedTo;
        $wasAssigned = $previousAssignee !== null && $previousAssignee > 0;
        $nowAssigned = $updated->assignedTo !== null && $updated->assignedTo > 0;

        if (! $wasAssigned && $nowAssigned) {
            $this->dispatchAssigned($updated, $actorPlatformUserId);
        }

        $previousStatus = $existing?->status;
        if (
            ($previousStatus === null || $previousStatus !== $updated->status)
            && $updated->status === Asset::STATUS_UNDER_REPAIR
        ) {
            $this->dispatchUnderRepair($updated, $actorPlatformUserId);
        }
    }

    private function dispatchAssigned(Asset $asset, int $actorPlatformUserId): void
    {
        if ($asset->assignedTo === null || $asset->assignedTo <= 0) {
            return;
        }

        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::ASSET_ASSIGNED,
                title: 'Asset assigned to you',
                body: $this->assetLabel($asset),
                entityType: 'asset',
                entityId: $asset->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('assets', 'assets')),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $asset->assignedTo],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchUnderRepair(Asset $asset, int $actorPlatformUserId): void
    {
        $recipientCodes = [StaffCoreEvent::RECIPIENT_HR];
        $context = [];

        if ($asset->assignedTo !== null && $asset->assignedTo > 0) {
            $recipientCodes[] = StaffCoreEvent::RECIPIENT_EMPLOYEE;
            $context['employee_id'] = $asset->assignedTo;
        }

        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::ASSET_STATUS_UNDER_REPAIR,
                title: 'Asset under repair',
                body: $this->assetLabel($asset),
                entityType: 'asset',
                entityId: $asset->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('assets', 'assets')),
                priority: Notification::PRIORITY_LOW,
                recipientCodes: $recipientCodes,
                context: $context,
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }
}


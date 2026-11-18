<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\AssetCategories\Services;

use MF\StaffCore\Modules\AssetCategories\DTO\AssetCategory;
use MF\StaffCore\Modules\AssetCategories\Repositories\AssetCategoryRepository;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class AssetCategoryService
{
    private const NAME_MAX_LENGTH = 255;

    public function __construct(
        private readonly AssetCategoryRepository $repository,
    ) {
    }

    /**
     * @return PaginatedList<AssetCategory>
     */
    public function listPage(int $page, int $perPage = PaginatedList::DEFAULT_PER_PAGE): PaginatedList
    {
        return $this->repository->findPage($page, $perPage);
    }

    public function get(int $id): ?AssetCategory
    {
        return $this->repository->findById($id);
    }

    /**
     * @return list<AssetCategory>
     */
    public function listAll(): array
    {
        return $this->repository->listAll();
    }

    public function create(string $name, ?string $description, int $userId): AssetCategory
    {
        $name = $this->normalizeName($name);
        $description = $this->normalizeDescription($description);
        $this->assertName($name);

        if ($this->repository->nameExists($name)) {
            throw new ValidationException(esc_html('A category with this name already exists.'));
        }

        return $this->repository->create($name, $description, $userId);
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        int $userId,
    ): AssetCategory {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Category not found.'));
        }

        $name = $this->normalizeName($name);
        $description = $this->normalizeDescription($description);
        $this->assertName($name);

        if ($this->repository->nameExists($name, $id)) {
            throw new ValidationException(esc_html('A category with this name already exists.'));
        }

        return $this->repository->update($id, $name, $description, $userId);
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Category not found.'));
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

    private function normalizeName(string $name): string
    {
        return trim($name);
    }

    private function normalizeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $trimmed = trim($description);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assertName(string $name): void
    {
        if ($name === '') {
            throw new ValidationException(esc_html('Category name is required.'));
        }

        if (strlen($name) > self::NAME_MAX_LENGTH) {
            throw new ValidationException(esc_html('Category name must be at most ' . self::NAME_MAX_LENGTH . ' characters.'));
        }
    }
}


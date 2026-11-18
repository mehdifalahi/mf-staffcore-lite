<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\AssetCategories\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\AssetCategories\DTO\AssetCategory;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class AssetCategoryRepository
{
    private const TABLE = 'asset_category';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<AssetCategory>
     */
    public function findPage(int $page, int $perPage): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);

        $total = (int) $this->db->getVar('SELECT COUNT(*) FROM `' . $table . '`');

        $rows = $this->db->getResults(
            'SELECT id, name, description, created_at, updated_at, created_by, updated_by
             FROM `' . $table . '`
             ORDER BY id DESC
             LIMIT %d OFFSET %d',
            [$perPage, $offset],
        );

        $items = array_map(
            static fn (array $row): AssetCategory => AssetCategory::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @return list<AssetCategory>
     */
    public function listAll(): array
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT id, name, description, created_at, updated_at, created_by, updated_by
             FROM `' . $table . '`
             ORDER BY name ASC',
        );

        return array_map(
            static fn (array $row): AssetCategory => AssetCategory::fromRow($row),
            $rows,
        );
    }

    public function findById(int $id): ?AssetCategory
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $row = $this->db->getRow(
            'SELECT id, name, description, created_at, updated_at, created_by, updated_by
             FROM `' . $table . '`
             WHERE id = %d
             LIMIT 1',
            [$id],
        );

        return $row !== null ? AssetCategory::fromRow($row) : null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE name = %s AND id != %d',
                [$name, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE name = %s',
            [$name],
        );

        return ((int) $count) > 0;
    }

    public function create(
        string $name,
        ?string $description,
        int $userId,
    ): AssetCategory {
        $id = $this->idSequence->nextId(self::TABLE);
        $now = gmdate('Y-m-d H:i:s');

        $ok = $this->db->insert(self::TABLE, [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ], ['%d', '%s', '%s', '%s', '%s', '%d', '%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to create asset category: ' . $this->db->getLastError()));
        }

        $category = $this->findById($id);

        if ($category === null) {
            throw new RuntimeException(esc_html('Asset category was created but could not be loaded.'));
        }

        return $category;
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        int $userId,
    ): AssetCategory {
        $ok = $this->db->update(
            self::TABLE,
            [
                'name' => $name,
                'description' => $description,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%d'],
            ['%d'],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update asset category: ' . $this->db->getLastError()));
        }

        $category = $this->findById($id);

        if ($category === null) {
            throw new RuntimeException(esc_html('Asset category was updated but could not be loaded.'));
        }

        return $category;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete asset category: ' . $this->db->getLastError()));
        }
    }
}


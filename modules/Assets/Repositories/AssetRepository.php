<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Assets\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Assets\DTO\Asset;
use MF\StaffCore\Modules\Assets\DTO\AssetInput;
use MF\StaffCore\Modules\Assets\DTO\AssetListFilter;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class AssetRepository
{
    private const TABLE = 'assets';

    private const SELECT_COLUMNS = 'a.id, a.code, a.name, a.category_id, a.purchase_date, a.purchase_cost,
        a.current_value, a.serial_number, a.warranty_expiry, a.status, a.`condition`, a.assigned_to,
        a.location, a.supplier_id, a.image, a.notes, a.created_at, a.updated_at, a.created_by, a.updated_by';

    private const JOIN_COLUMNS = ', ac.name AS category_name,
        TRIM(CONCAT(e.first_name, \' \', e.last_name)) AS assigned_to_name,
        s.supplier_name';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Asset>
     */
    public function findPage(int $page, int $perPage, ?AssetListFilter $filter = null): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $categoryTable = $this->db->resolveTableName('asset_category');
        $employeeTable = $this->db->resolveTableName('employee');
        $supplierTable = $this->db->resolveTableName('supplier');

        [$whereSql, $whereParams] = $this->buildFilterWhere($filter);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS a WHERE ' . $whereSql,
            $whereParams,
        );

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_COLUMNS . '
             FROM `' . $table . '` AS a
             INNER JOIN `' . $categoryTable . '` AS ac ON ac.id = a.category_id
             LEFT JOIN `' . $employeeTable . '` AS e ON e.id = a.assigned_to
             LEFT JOIN `' . $supplierTable . '` AS s ON s.id = a.supplier_id
             WHERE ' . $whereSql . '
             ORDER BY a.id DESC
             LIMIT %d OFFSET %d',
            array_merge($whereParams, [$perPage, $offset]),
        );

        $items = array_map(
            static fn (array $row): Asset => Asset::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    public function findById(int $id): ?Asset
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $categoryTable = $this->db->resolveTableName('asset_category');
        $employeeTable = $this->db->resolveTableName('employee');
        $supplierTable = $this->db->resolveTableName('supplier');

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_COLUMNS . '
             FROM `' . $table . '` AS a
             INNER JOIN `' . $categoryTable . '` AS ac ON ac.id = a.category_id
             LEFT JOIN `' . $employeeTable . '` AS e ON e.id = a.assigned_to
             LEFT JOIN `' . $supplierTable . '` AS s ON s.id = a.supplier_id
             WHERE a.id = %d
             LIMIT 1',
            [$id],
        );

        return $row !== null ? Asset::fromRow($row) : null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE code = %s AND id != %d',
                [$code, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE code = %s',
            [$code],
        );

        return ((int) $count) > 0;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function findEmployeeOptions(): array
    {
        $table = $this->db->resolveTableName('employee');
        $rows = $this->db->getResults(
            'SELECT id, first_name, last_name
             FROM `' . $table . '`
             WHERE employment_status = %s
             ORDER BY first_name ASC, last_name ASC',
            ['ACTIVE'],
        );

        $options = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $options[] = [
                'id' => $id,
                'label' => trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
            ];
        }

        return $options;
    }

    public function create(AssetInput $input, int $userId): Asset
    {
        $id = $this->idSequence->nextId(self::TABLE);
        $now = gmdate('Y-m-d H:i:s');

        $data = array_merge($input->toDatabaseRow(), [
            'id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $ok = $this->db->insert(self::TABLE, $data);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to create asset: ' . $this->db->getLastError()));
        }

        $asset = $this->findById($id);

        if ($asset === null) {
            throw new RuntimeException(esc_html('Asset was created but could not be loaded.'));
        }

        return $asset;
    }

    public function update(int $id, AssetInput $input, int $userId): Asset
    {
        $data = array_merge($input->toDatabaseRow(), [
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);

        $ok = $this->db->update(
            self::TABLE,
            $data,
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update asset: ' . $this->db->getLastError()));
        }

        $asset = $this->findById($id);

        if ($asset === null) {
            throw new RuntimeException(esc_html('Asset was updated but could not be loaded.'));
        }

        return $asset;
    }

    public function clearImage(int $id, int $userId): Asset
    {
        $ok = $this->db->update(
            self::TABLE,
            [
                'image' => null,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ],
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to remove asset image: ' . $this->db->getLastError()));
        }

        $asset = $this->findById($id);

        if ($asset === null) {
            throw new RuntimeException(esc_html('Asset image was removed but the record could not be loaded.'));
        }

        return $asset;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete asset: ' . $this->db->getLastError()));
        }
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?AssetListFilter $filter): array
    {
        $clauses = ['1=1'];
        $params = [];

        if ($filter === null) {
            return [implode(' AND ', $clauses), $params];
        }

        if ($filter->categoryId !== null) {
            $clauses[] = 'a.category_id = %d';
            $params[] = $filter->categoryId;
        }

        if ($filter->status !== null) {
            $clauses[] = 'a.status = %s';
            $params[] = $filter->status;
        }

        if ($filter->assignedToEmployeeId !== null) {
            $clauses[] = 'a.assigned_to = %d';
            $params[] = $filter->assignedToEmployeeId;
        }

        if ($filter->search !== null) {
            $like = '%' . $this->escapeLike($filter->search) . '%';
            $clauses[] = '(a.code LIKE %s OR a.name LIKE %s OR a.serial_number LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return [implode(' AND ', $clauses), $params];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}


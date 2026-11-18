<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Suppliers\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Suppliers\DTO\Supplier;
use MF\StaffCore\Modules\Suppliers\DTO\SupplierInput;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class SupplierRepository
{
    private const TABLE = 'supplier';

    private const SELECT_COLUMNS = 'id, supplier_name, contact_person, email, phone, address, city, state, country,
        postal_code, website, payment_terms, status, notes, created_at, updated_at, created_by, updated_by';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Supplier>
     */
    public function findPage(int $page, int $perPage): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);

        $total = (int) $this->db->getVar('SELECT COUNT(*) FROM `' . $table . '`');

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '`
             ORDER BY id DESC
             LIMIT %d OFFSET %d',
            [$perPage, $offset],
        );

        $items = array_map(
            static fn (array $row): Supplier => Supplier::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @return list<Supplier>
     */
    public function listAll(): array
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '`
             WHERE status = %s
             ORDER BY supplier_name ASC',
            [Supplier::STATUS_ACTIVE],
        );

        return array_map(
            static fn (array $row): Supplier => Supplier::fromRow($row),
            $rows,
        );
    }

    public function findById(int $id): ?Supplier
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '`
             WHERE id = %d
             LIMIT 1',
            [$id],
        );

        return $row !== null ? Supplier::fromRow($row) : null;
    }

    public function nameExists(string $supplierName, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE supplier_name = %s AND id != %d',
                [$supplierName, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE supplier_name = %s',
            [$supplierName],
        );

        return ((int) $count) > 0;
    }

    public function create(SupplierInput $input, int $userId): Supplier
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
            throw new RuntimeException(esc_html('Failed to create supplier: ' . $this->db->getLastError()));
        }

        $supplier = $this->findById($id);

        if ($supplier === null) {
            throw new RuntimeException(esc_html('Supplier was created but could not be loaded.'));
        }

        return $supplier;
    }

    public function update(int $id, SupplierInput $input, int $userId): Supplier
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
            throw new RuntimeException(esc_html('Failed to update supplier: ' . $this->db->getLastError()));
        }

        $supplier = $this->findById($id);

        if ($supplier === null) {
            throw new RuntimeException(esc_html('Supplier was updated but could not be loaded.'));
        }

        return $supplier;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete supplier: ' . $this->db->getLastError()));
        }
    }
}


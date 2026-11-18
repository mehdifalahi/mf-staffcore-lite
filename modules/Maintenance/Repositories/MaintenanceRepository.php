<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Maintenance\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Maintenance\DTO\Maintenance;
use MF\StaffCore\Modules\Maintenance\DTO\MaintenanceInput;
use MF\StaffCore\Modules\Maintenance\DTO\MaintenanceListFilter;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class MaintenanceRepository
{
    private const TABLE = 'maintenance';

    private const SELECT_COLUMNS = 'm.id, m.asset_id, m.maintenance_date, m.type, m.cost, m.performed_by,
        m.performed_by_employee_id, m.next_service_date, m.notes, m.created_at, m.updated_at,
        m.created_by, m.updated_by';

    private const JOIN_ASSET = ', a.name AS asset_name, a.code AS asset_code';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Maintenance>
     */
    public function findPage(int $page, int $perPage, ?MaintenanceListFilter $filter = null): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $assetTable = $this->db->resolveTableName('assets');

        [$whereSql, $whereParams] = $this->buildFilterWhere($filter);

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` AS m WHERE ' . $whereSql,
            $whereParams,
        );

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_ASSET . '
             FROM `' . $table . '` AS m
             INNER JOIN `' . $assetTable . '` AS a ON a.id = m.asset_id
             WHERE ' . $whereSql . '
             ORDER BY m.maintenance_date DESC, m.id DESC
             LIMIT %d OFFSET %d',
            array_merge($whereParams, [$perPage, $offset]),
        );

        $items = array_map(
            static fn (array $row): Maintenance => Maintenance::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    public function findById(int $id): ?Maintenance
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $assetTable = $this->db->resolveTableName('assets');

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_ASSET . '
             FROM `' . $table . '` AS m
             INNER JOIN `' . $assetTable . '` AS a ON a.id = m.asset_id
             WHERE m.id = %d
             LIMIT 1',
            [$id],
        );

        return $row !== null ? Maintenance::fromRow($row) : null;
    }

    public function assetExists(int $assetId): bool
    {
        $table = $this->db->resolveTableName('assets');
        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE id = %d',
            [$assetId],
        );

        return ((int) $count) > 0;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function findAssetOptions(): array
    {
        $table = $this->db->resolveTableName('assets');
        $rows = $this->db->getResults(
            'SELECT id, code, name
             FROM `' . $table . '`
             ORDER BY name ASC',
        );

        $options = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $name = (string) ($row['name'] ?? '');
            $code = (string) ($row['code'] ?? '');
            $label = $code !== '' ? $name . ' (' . $code . ')' : $name;

            $options[] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function findEmployeeOptions(): array
    {
        $table = $this->db->resolveTableName('employee');
        $rows = $this->db->getResults(
            'SELECT id, first_name, last_name, code
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

            $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $code = (string) ($row['code'] ?? '');
            $label = $code !== '' ? $name . ' (' . $code . ')' : $name;

            $options[] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        return $options;
    }

    public function findEmployeeLabel(int $employeeId): ?string
    {
        $table = $this->db->resolveTableName('employee');
        $row = $this->db->getRow(
            'SELECT first_name, last_name, code
             FROM `' . $table . '`
             WHERE id = %d
             LIMIT 1',
            [$employeeId],
        );

        if ($row === null) {
            return null;
        }

        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        $code = (string) ($row['code'] ?? '');

        return $code !== '' ? $name . ' (' . $code . ')' : $name;
    }

    public function employeeExists(int $employeeId): bool
    {
        $table = $this->db->resolveTableName('employee');
        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE id = %d',
            [$employeeId],
        );

        return ((int) $count) > 0;
    }

    public function create(MaintenanceInput $input, int $userId): Maintenance
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
            throw new RuntimeException(esc_html('Failed to create maintenance record: ' . $this->db->getLastError()));
        }

        $maintenance = $this->findById($id);

        if ($maintenance === null) {
            throw new RuntimeException(esc_html('Maintenance record was created but could not be loaded.'));
        }

        return $maintenance;
    }

    public function update(int $id, MaintenanceInput $input, int $userId): Maintenance
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
            throw new RuntimeException(esc_html('Failed to update maintenance record: ' . $this->db->getLastError()));
        }

        $maintenance = $this->findById($id);

        if ($maintenance === null) {
            throw new RuntimeException(esc_html('Maintenance record was updated but could not be loaded.'));
        }

        return $maintenance;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete maintenance record: ' . $this->db->getLastError()));
        }
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?MaintenanceListFilter $filter): array
    {
        $clauses = ['1=1'];
        $params = [];

        if ($filter === null) {
            return [implode(' AND ', $clauses), $params];
        }

        if ($filter->assetId !== null) {
            $clauses[] = 'm.asset_id = %d';
            $params[] = $filter->assetId;
        }

        if ($filter->type !== null) {
            $clauses[] = 'm.type = %s';
            $params[] = $filter->type;
        }

        if ($filter->performedByEmployeeId !== null) {
            $clauses[] = 'm.performed_by_employee_id = %d';
            $params[] = $filter->performedByEmployeeId;
        }

        if ($filter->dateFrom !== null) {
            $clauses[] = 'm.maintenance_date >= %s';
            $params[] = $filter->dateFrom;
        }

        if ($filter->dateTo !== null) {
            $clauses[] = 'm.maintenance_date <= %s';
            $params[] = $filter->dateTo;
        }

        return [implode(' AND ', $clauses), $params];
    }
}


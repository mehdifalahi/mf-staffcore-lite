<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Positions\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Positions\DTO\Position;
use MF\StaffCore\Modules\Positions\DTO\PositionInput;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class PositionRepository
{
    private const TABLE = 'position';

    private const SELECT_COLUMNS = 'p.id, p.title, p.code, p.department_id, p.level, p.min_salary,
        p.max_salary, p.description, p.status, p.created_at, p.updated_at, p.created_by, p.updated_by';

    private const JOIN_COLUMNS = ', d.name AS department_name';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<Position>
     */
    public function findPage(int $page, int $perPage): PaginatedList
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);
        $departmentTable = $this->db->resolveTableName('department');

        $total = (int) $this->db->getVar('SELECT COUNT(*) FROM `' . $table . '` AS p');

        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_COLUMNS . '
             FROM `' . $table . '` AS p
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = p.department_id
             ORDER BY p.id DESC
             LIMIT %d OFFSET %d',
            [$perPage, $offset],
        );

        $items = array_map(
            static fn (array $row): Position => Position::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @return list<Position>
     */
    public function listActive(): array
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '` AS p
             WHERE p.status = %s
             ORDER BY p.department_id ASC, p.title ASC',
            [Position::STATUS_ACTIVE],
        );

        return array_map(
            static fn (array $row): Position => Position::fromRow($row),
            $rows,
        );
    }

    /**
     * @return list<Position>
     */
    public function listActiveByDepartment(int $departmentId): array
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM `' . $table . '` AS p
             WHERE p.department_id = %d AND p.status = %s
             ORDER BY p.title ASC',
            [$departmentId, Position::STATUS_ACTIVE],
        );

        return array_map(
            static fn (array $row): Position => Position::fromRow($row),
            $rows,
        );
    }

    public function findFirstActiveIdForDepartment(int $departmentId, ?string $preferredLevel = null): ?int
    {
        if ($departmentId <= 0) {
            return null;
        }

        $table = $this->db->resolveTableName(self::TABLE);

        if ($preferredLevel !== null && $preferredLevel !== '') {
            $row = $this->db->getRow(
                'SELECT id FROM `' . $table . '`
                 WHERE department_id = %d AND status = %s AND level = %s
                 ORDER BY id ASC
                 LIMIT 1',
                [$departmentId, Position::STATUS_ACTIVE, $preferredLevel],
            );

            if ($row !== null) {
                $id = (int) ($row['id'] ?? 0);

                if ($id > 0) {
                    return $id;
                }
            }
        }

        $row = $this->db->getRow(
            'SELECT id FROM `' . $table . '`
             WHERE department_id = %d AND status = %s
             ORDER BY id ASC
             LIMIT 1',
            [$departmentId, Position::STATUS_ACTIVE],
        );

        if ($row === null) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function findFirstActiveId(): ?int
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $row = $this->db->getRow(
            'SELECT id FROM `' . $table . '`
             WHERE status = %s
             ORDER BY id ASC
             LIMIT 1',
            [Position::STATUS_ACTIVE],
        );

        if ($row === null) {
            return null;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function findById(int $id): ?Position
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $departmentTable = $this->db->resolveTableName('department');

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . self::JOIN_COLUMNS . '
             FROM `' . $table . '` AS p
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = p.department_id
             WHERE p.id = %d
             LIMIT 1',
            [$id],
        );

        return $row !== null ? Position::fromRow($row) : null;
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

    public function create(PositionInput $input, int $userId): Position
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
            throw new RuntimeException(esc_html('Failed to create position: ' . $this->db->getLastError()));
        }

        $position = $this->findById($id);

        if ($position === null) {
            throw new RuntimeException(esc_html('Position was created but could not be loaded.'));
        }

        return $position;
    }

    public function update(int $id, PositionInput $input, int $userId): Position
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
            throw new RuntimeException(esc_html('Failed to update position: ' . $this->db->getLastError()));
        }

        $position = $this->findById($id);

        if ($position === null) {
            throw new RuntimeException(esc_html('Position was updated but could not be loaded.'));
        }

        return $position;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete position: ' . $this->db->getLastError()));
        }
    }
}


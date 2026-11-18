<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Users\DTO\StaffUser;
use MF\StaffCore\Modules\Users\DTO\StaffUserInput;
use MF\StaffCore\Modules\Users\DTO\UserListFilter;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;

final class StaffUserRepository
{
    private const TABLE = 'staff_user';

    private const SELECT_COLUMNS = 'id, platform_user_id, role, manager_scope_mode, phone, status, failed_login_attempts,
        created_at, updated_at, created_by, updated_by';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return PaginatedList<StaffUser>
     */
    public function findPage(
        int $page,
        int $perPage,
        ?UserListFilter $filter = null,
        ?array $platformUserIds = null,
    ): PaginatedList {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $table = $this->db->resolveTableName(self::TABLE);

        [$whereSql, $params] = $this->buildFilterWhere($filter, $platformUserIds);

        if ($whereSql === '0=1') {
            return new PaginatedList([], 0, $page, $perPage);
        }

        $total = (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE ' . $whereSql,
            $params,
        );

        $listParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->db->getResults(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM `' . $table . '`
             WHERE ' . $whereSql . '
             ORDER BY id DESC
             LIMIT %d OFFSET %d',
            $listParams,
        );

        $items = array_map(
            static fn (array $row): StaffUser => StaffUser::fromRow($row),
            $rows,
        );

        return new PaginatedList($items, $total, $page, $perPage);
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFilterWhere(?UserListFilter $filter, ?array $platformUserIds): array
    {
        $clauses = ['1=1'];
        $params = [];

        if ($platformUserIds !== null) {
            if ($platformUserIds === []) {
                return ['0=1', []];
            }

            $placeholders = implode(',', array_fill(0, count($platformUserIds), '%d'));
            $clauses[] = 'platform_user_id IN (' . $placeholders . ')';
            $params = array_merge($params, $platformUserIds);
        }

        if ($filter !== null) {
            if ($filter->role !== null) {
                $clauses[] = 'role = %s';
                $params[] = $filter->role;
            }

            if ($filter->status !== null) {
                $clauses[] = 'status = %s';
                $params[] = $filter->status;
            }
        }

        return [implode(' AND ', $clauses), $params];
    }

    public function findById(int $id): ?StaffUser
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM `' . $table . '` WHERE id = %d LIMIT 1',
            [$id],
        );

        return $row !== null ? StaffUser::fromRow($row) : null;
    }

    public function findByPlatformUserId(int $platformUserId): ?StaffUser
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM `' . $table . '`
             WHERE platform_user_id = %d LIMIT 1',
            [$platformUserId],
        );

        return $row !== null ? StaffUser::fromRow($row) : null;
    }

    /**
     * @return list<int>
     */
    public function listActivePlatformUserIds(): array
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $rows = $this->db->getResults(
            'SELECT platform_user_id FROM `' . $table . '`
             WHERE status = %s
             ORDER BY platform_user_id ASC',
            [StaffUser::STATUS_ACTIVE],
        );

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['platform_user_id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Active StaffCore accounts not linked to another employee record.
     *
     * @return list<int>
     */
    public function listUnlinkedActivePlatformUserIds(?int $excludeEmployeeId = null): array
    {
        $staffTable = $this->db->resolveTableName(self::TABLE);
        $employeeTable = $this->db->resolveTableName('employee');

        if ($excludeEmployeeId !== null && $excludeEmployeeId > 0) {
            $rows = $this->db->getResults(
                'SELECT su.platform_user_id
                 FROM `' . $staffTable . '` su
                 WHERE su.status = %s
                   AND su.platform_user_id > 0
                   AND NOT EXISTS (
                       SELECT 1 FROM `' . $employeeTable . '` e
                       WHERE e.user_id = su.platform_user_id AND e.id != %d
                   )
                 ORDER BY su.platform_user_id ASC',
                [StaffUser::STATUS_ACTIVE, $excludeEmployeeId],
            );
        } else {
            $rows = $this->db->getResults(
                'SELECT su.platform_user_id
                 FROM `' . $staffTable . '` su
                 LEFT JOIN `' . $employeeTable . '` e ON e.user_id = su.platform_user_id
                 WHERE su.status = %s
                   AND su.platform_user_id > 0
                   AND e.user_id IS NULL
                 ORDER BY su.platform_user_id ASC',
                [StaffUser::STATUS_ACTIVE],
            );
        }

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['platform_user_id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    public function listActivePlatformUserIdsByRole(string $role): array
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $rows = $this->db->getResults(
            'SELECT platform_user_id FROM `' . $table . '`
             WHERE role = %s AND status = %s
             ORDER BY platform_user_id ASC',
            [$role, StaffUser::STATUS_ACTIVE],
        );

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['platform_user_id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function isActivePlatformUserWithRole(int $platformUserId, string $role): bool
    {
        if ($platformUserId <= 0) {
            return false;
        }

        $table = $this->db->resolveTableName(self::TABLE);

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '`
             WHERE platform_user_id = %d AND role = %s AND status = %s',
            [$platformUserId, $role, StaffUser::STATUS_ACTIVE],
        );

        return ((int) $count) > 0;
    }

    /**
     * @return list<int>
     */
    public function listAllPlatformUserIds(): array
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $rows = $this->db->getResults(
            'SELECT platform_user_id FROM `' . $table . '` ORDER BY platform_user_id ASC',
        );

        return array_map(
            static fn (array $row): int => (int) ($row['platform_user_id'] ?? 0),
            $rows,
        );
    }

    public function platformUserIdExists(int $platformUserId, ?int $excludeId = null): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        if ($excludeId !== null) {
            $count = $this->db->getVar(
                'SELECT COUNT(*) FROM `' . $table . '` WHERE platform_user_id = %d AND id != %d',
                [$platformUserId, $excludeId],
            );

            return ((int) $count) > 0;
        }

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE platform_user_id = %d',
            [$platformUserId],
        );

        return ((int) $count) > 0;
    }

    public function create(int $platformUserId, StaffUserInput $input, int $userId): StaffUser
    {
        $id = $this->idSequence->nextId(self::TABLE);
        $now = gmdate('Y-m-d H:i:s');

        $data = array_merge($input->toDatabaseRow(), [
            'id' => $id,
            'platform_user_id' => $platformUserId,
            'failed_login_attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $ok = $this->db->insert(self::TABLE, $data);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to create staff user: ' . $this->db->getLastError()));
        }

        $staffUser = $this->findById($id);

        if ($staffUser === null) {
            throw new RuntimeException(esc_html('Staff user was created but could not be loaded.'));
        }

        return $staffUser;
    }

    public function update(int $id, StaffUserInput $input, int $userId): StaffUser
    {
        $data = array_merge($input->toDatabaseRow(), [
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);

        $ok = $this->db->update(self::TABLE, $data, ['id' => $id]);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update staff user: ' . $this->db->getLastError()));
        }

        $staffUser = $this->findById($id);

        if ($staffUser === null) {
            throw new RuntimeException(esc_html('Staff user was updated but could not be loaded.'));
        }

        return $staffUser;
    }

    public function resetLoginAttempts(int $id, int $userId): StaffUser
    {
        $ok = $this->db->update(
            self::TABLE,
            [
                'failed_login_attempts' => 0,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ],
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to reset login attempts: ' . $this->db->getLastError()));
        }

        $staffUser = $this->findById($id);

        if ($staffUser === null) {
            throw new RuntimeException(esc_html('Staff user could not be loaded after reset.'));
        }

        return $staffUser;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete(self::TABLE, ['id' => $id], ['%d']);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to delete staff user: ' . $this->db->getLastError()));
        }
    }
}


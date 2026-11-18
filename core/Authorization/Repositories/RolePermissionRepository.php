<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization\Repositories;

use MF\StaffCore\Database\TableGateway;

final class RolePermissionRepository
{
    private const TABLE = 'role_permission';

    public function __construct(
        private readonly TableGateway $db,
    ) {
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $roleId): array
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $rows = $this->db->getResults(
            'SELECT permission FROM `' . $table . '` WHERE role = %s ORDER BY permission ASC',
            [$roleId],
        );

        return array_map(
            static fn (array $row): string => (string) ($row['permission'] ?? ''),
            $rows,
        );
    }

    public function roleHasPermission(string $roleId, string $permission): bool
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $count = $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE role = %s AND permission = %s',
            [$roleId, $permission],
        );

        return ((int) $count) > 0;
    }

    public function countForRole(string $roleId): int
    {
        $table = $this->db->resolveTableName(self::TABLE);

        return (int) $this->db->getVar(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE role = %s',
            [$roleId],
        );
    }

    /**
     * @param list<string> $permissions
     */
    public function replaceForRole(string $roleId, array $permissions): void
    {
        $this->db->delete(self::TABLE, ['role' => $roleId]);

        foreach ($permissions as $permission) {
            $this->db->insert(self::TABLE, [
                'role' => $roleId,
                'permission' => $permission,
            ]);
        }
    }
}

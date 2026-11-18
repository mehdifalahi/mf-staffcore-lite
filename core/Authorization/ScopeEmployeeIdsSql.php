<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

/**
 * Builds SQL fragments for {@see ManagerTeamScopeResolver} list filters.
 *
 * @phpstan-type SqlFragment array{0: string, 1: list<mixed>}
 */
final class ScopeEmployeeIdsSql
{
    /**
     * @param list<int>|null $scopeEmployeeIds null = unrestricted, [] = no rows
     * @return SqlFragment
     */
    public static function andIn(string $columnExpression, ?array $scopeEmployeeIds): array
    {
        return self::fragment($columnExpression, $scopeEmployeeIds, ' AND ');
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return SqlFragment
     */
    public static function whereIn(string $columnExpression, ?array $scopeEmployeeIds): array
    {
        return self::fragment($columnExpression, $scopeEmployeeIds, '');
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return SqlFragment
     */
    private static function fragment(string $columnExpression, ?array $scopeEmployeeIds, string $prefix): array
    {
        if ($scopeEmployeeIds === null) {
            return ['', []];
        }

        if ($scopeEmployeeIds === []) {
            return [$prefix . '0 = 1', []];
        }

        if (count($scopeEmployeeIds) === 1) {
            return [$prefix . $columnExpression . ' = %d', [$scopeEmployeeIds[0]]];
        }

        $placeholders = implode(', ', array_fill(0, count($scopeEmployeeIds), '%d'));

        return [$prefix . $columnExpression . ' IN (' . $placeholders . ')', $scopeEmployeeIds];
    }
}

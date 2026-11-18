<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Lite logical-table catalog.
 *
 * Paid-module tables are absent from the Lite package and created later,
 * non-destructively, when a site replaces Lite with the Pro edition.
 */
final class LogicalTables
{
    public const ANCHOR = 'company_information';

    /** @var list<string> */
    public const ALL = [
        'maintenance',
        'attendance',
        'leave_request',
        'payroll',
        'employee_allowance',
        'assets',
        'supplier',
        'asset_category',
        'department',
        'position',
        'employee',
        'company_information',
        'staff_user',
        'notification',
        'role_permission',
    ];

    /**
     * @return list<string>
     */
    public static function sortedForReplacement(): array
    {
        $tables = self::ALL;
        usort(
            $tables,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
        );

        return $tables;
    }
}

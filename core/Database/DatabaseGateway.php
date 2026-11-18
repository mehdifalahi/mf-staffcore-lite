<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Platform-agnostic database access for schema installation.
 */
interface DatabaseGateway
{
    public function resolveTableName(string $logicalTableName): string;

    public function tableExists(string $logicalTableName): bool;

    public function getCharsetCollate(): string;

    /**
     * @return bool True when the statement executed without a driver error.
     */
    public function query(string $sql, bool $suppressErrors = false): bool;

    public function getLastError(): string;
}

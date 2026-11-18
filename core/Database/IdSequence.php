<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Generates the next primary key for tables without AUTO_INCREMENT.
 */
interface IdSequence
{
    public function nextId(string $logicalTableName): int;
}

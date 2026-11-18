<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Database;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;

final class WordPressIdSequence implements IdSequence
{
    public function __construct(
        private readonly TableGateway $gateway,
    ) {
    }

    public function nextId(string $logicalTableName): int
    {
        $table = $this->gateway->resolveTableName($logicalTableName);
        $max = $this->gateway->getVar(
            'SELECT MAX(id) FROM `' . $table . '`'
        );

        return ((int) $max) + 1;
    }
}

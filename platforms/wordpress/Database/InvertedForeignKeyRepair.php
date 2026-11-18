<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Database;

use MF\StaffCore\Database\ForeignKeyStatements;
use MF\StaffCore\Database\LogicalTables;
use wpdb;

/**
 * Removes legacy inverted FK constraints (parent.id -> child.fk) and applies canonical FKs.
 */
final class InvertedForeignKeyRepair
{
    public const REPAIR_OPTION = 'mf_staffcore_fk_repair_inverted_v1';

    public function __construct(
        private readonly wpdb $wpdb,
        private readonly WordPressDatabaseGateway $gateway,
    ) {
    }

    public function isRepaired(): bool
    {
        return get_option(self::REPAIR_OPTION, '') === '1';
    }

    public function repair(): void
    {
        if ($this->isRepaired()) {
            return;
        }

        $this->dropInvertedForeignKeys();
        $this->applyCanonicalForeignKeys();

        update_option(self::REPAIR_OPTION, '1', false);
    }

    private function dropInvertedForeignKeys(): void
    {
        $tablePrefix = $this->wpdb->prefix . WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX;
        $like = $this->wpdb->esc_like($tablePrefix) . '%';
        $schema = defined('DB_NAME') ? DB_NAME : '';

        if ($schema === '') {
            return;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- query uses $wpdb->prepare() with bound placeholders
        $prepared = $this->wpdb->prepare(
            'SELECT CONSTRAINT_NAME, TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = %s
               AND TABLE_NAME LIKE %s
               AND REFERENCED_TABLE_NAME IS NOT NULL
               AND COLUMN_NAME = %s
               AND REFERENCED_COLUMN_NAME <> %s',
            $schema,
            $like,
            'id',
            'id',
        );
        $rows = $this->wpdb->get_results($prepared);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $constraint = (string) ($row->CONSTRAINT_NAME ?? '');
            $table = (string) ($row->TABLE_NAME ?? '');

            if ($constraint === '' || $table === '') {
                continue;
            }

            $this->wpdb->query(
                'ALTER TABLE `' . esc_sql($table) . '` DROP FOREIGN KEY `' . esc_sql($constraint) . '`',
            );
        }
    }

    private function applyCanonicalForeignKeys(): void
    {
        foreach (ForeignKeyStatements::canonicalAlterStatements() as $statement) {
            $prepared = $this->prefixStatement($statement);
            $this->gateway->query($prepared, true);
        }
    }

    private function prefixStatement(string $statement): string
    {
        foreach (LogicalTables::sortedForReplacement() as $logical) {
            $physical = $this->gateway->resolveTableName($logical);
            $statement = str_replace(
                '`' . $logical . '`',
                '`' . $physical . '`',
                $statement,
            );
        }

        return $statement;
    }
}

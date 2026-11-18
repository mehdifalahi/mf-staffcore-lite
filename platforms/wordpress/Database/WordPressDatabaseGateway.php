<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Database;

use MF\StaffCore\Database\DatabaseGateway;
use wpdb;

final class WordPressDatabaseGateway implements DatabaseGateway
{
    public const PLUGIN_TABLE_PREFIX = 'mfsc_';

    public function __construct(
        private readonly wpdb $wpdb,
        private readonly string $pluginTablePrefix,
    ) {
    }

    public function resolveTableName(string $logicalTableName): string
    {
        return $this->wpdb->prefix . $this->pluginTablePrefix . $logicalTableName;
    }

    public function tableExists(string $logicalTableName): bool
    {
        $table = $this->resolveTableName($logicalTableName);
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- table name bound via prepare LIKE %s
        $prepared = $this->wpdb->prepare('SHOW TABLES LIKE %s', $table);
        $found = $this->wpdb->get_var($prepared);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

        return $found === $table;
    }

    public function getCharsetCollate(): string
    {
        return $this->wpdb->get_charset_collate();
    }

    public function query(string $sql, bool $suppressErrors = false): bool
    {
        $previousSuppress = $this->wpdb->suppress_errors;

        if ($suppressErrors) {
            $this->wpdb->suppress_errors(true);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DDL/DML from trusted schema installer (not user input)
        $result = $this->wpdb->query($sql);
        $this->wpdb->suppress_errors($previousSuppress);

        return $result !== false;
    }

    public function getLastError(): string
    {
        return (string) $this->wpdb->last_error;
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Database;

use MF\StaffCore\Database\TableGateway;
use wpdb;

final class WordPressTableGateway implements TableGateway
{
    public function __construct(
        private readonly wpdb $wpdb,
        private readonly string $pluginTablePrefix,
    ) {
    }

    public function resolveTableName(string $logicalTableName): string
    {
        return $this->wpdb->prefix . $this->pluginTablePrefix . $logicalTableName;
    }

    public function getVar(string $sql, array $params = []): mixed
    {
        if ($params !== []) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared immediately below
            return $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params));
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- repository SQL without unbound placeholders
        return $this->wpdb->get_var($sql);
    }

    public function getRow(string $sql, array $params = []): ?array
    {
        if ($params !== []) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared immediately below
            $row = $this->wpdb->get_row($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- repository SQL without unbound placeholders
            $row = $this->wpdb->get_row($sql, ARRAY_A);
        }

        return is_array($row) ? $row : null;
    }

    public function getResults(string $sql, array $params = []): array
    {
        if ($params !== []) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared immediately below
            $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- repository SQL without unbound placeholders
            $rows = $this->wpdb->get_results($sql, ARRAY_A);
        }

        if (! is_array($rows)) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    public function insert(string $logicalTableName, array $data, array $formats = []): bool
    {
        $table = $this->resolveTableName($logicalTableName);

        if ($formats === []) {
            $formats = $this->inferFormats($data);
        }

        return $this->wpdb->insert($table, $data, $formats) !== false;
    }

    public function update(
        string $logicalTableName,
        array $data,
        array $where,
        array $dataFormats = [],
        array $whereFormats = [],
    ): bool {
        $table = $this->resolveTableName($logicalTableName);

        if ($dataFormats === []) {
            $dataFormats = $this->inferFormats($data);
        }

        if ($whereFormats === []) {
            $whereFormats = $this->inferFormats($where);
        }

        return $this->wpdb->update($table, $data, $where, $dataFormats, $whereFormats) !== false;
    }

    public function delete(string $logicalTableName, array $where, array $whereFormats = []): bool
    {
        $table = $this->resolveTableName($logicalTableName);

        if ($whereFormats === []) {
            $whereFormats = $this->inferFormats($where);
        }

        return $this->wpdb->delete($table, $where, $whereFormats) !== false;
    }

    public function getLastError(): string
    {
        return (string) $this->wpdb->last_error;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function inferFormats(array $data): array
    {
        $formats = [];

        foreach ($data as $value) {
            if (is_int($value)) {
                $formats[] = '%d';
                continue;
            }

            if (is_float($value)) {
                $formats[] = '%f';
                continue;
            }

            $formats[] = '%s';
        }

        return $formats;
    }
}

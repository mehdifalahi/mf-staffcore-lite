<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Platform-agnostic table access for repositories (CRUD helpers).
 */
interface TableGateway
{
    public function resolveTableName(string $logicalTableName): string;

    /**
     * @param array<int, mixed> $params
     */
    public function getVar(string $sql, array $params = []): mixed;

    /**
     * @param array<int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function getRow(string $sql, array $params = []): ?array;

    /**
     * @param array<int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function getResults(string $sql, array $params = []): array;

    /**
     * @param array<string, mixed> $data
     * @param array<string> $formats wpdb format chars (e.g. %s, %d)
     */
    public function insert(string $logicalTableName, array $data, array $formats = []): bool;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     * @param array<string> $dataFormats
     * @param array<string> $whereFormats
     */
    public function update(
        string $logicalTableName,
        array $data,
        array $where,
        array $dataFormats = [],
        array $whereFormats = [],
    ): bool;

    /**
     * @param array<string, mixed> $where
     * @param array<string> $whereFormats
     */
    public function delete(string $logicalTableName, array $where, array $whereFormats = []): bool;

    public function getLastError(): string;
}

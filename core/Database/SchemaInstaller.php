<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

use RuntimeException;

/**
 * Installs canonical DDL when schema tables are missing. Idempotent and non-destructive.
 */
final class SchemaInstaller
{
    public const SCHEMA_VERSION = '1.0.0';

    public function __construct(
        private readonly DatabaseGateway $database,
        private readonly InstallStateStore $state,
        private readonly string $sqlFilePath,
        /** @var list<string> */
        private readonly array $requiredTables = LogicalTables::ALL,
        /** @var list<string> */
        private readonly array $tableNamesForReplacement = LogicalTables::ALL,
    ) {
    }

    public function isInstalled(): bool
    {
        foreach ($this->requiredTables as $logicalTable) {
            if (! $this->database->tableExists($logicalTable)) {
                return false;
            }
        }

        return true;
    }

    public function ensureInstalled(): bool
    {
        if ($this->isInstalled()) {
            $this->syncInstalledVersion();

            return true;
        }

        $this->install();

        if ($this->isInstalled()) {
            $this->state->setInstalledVersion(self::SCHEMA_VERSION);

            return true;
        }

        return false;
    }

    private function syncInstalledVersion(): void
    {
        if ($this->state->getInstalledVersion() !== self::SCHEMA_VERSION) {
            $this->state->setInstalledVersion(self::SCHEMA_VERSION);
        }
    }

    private function install(): void
    {
        if (! is_readable($this->sqlFilePath)) {
            throw new RuntimeException(
                esc_html('Schema install file is missing: ' . $this->sqlFilePath)
            );
        }

        $sql = file_get_contents($this->sqlFilePath);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException(
                esc_html('Schema install file is empty: ' . $this->sqlFilePath)
            );
        }

        foreach ($this->parseStatements($sql) as $statement) {
            $prepared = $this->prepareStatement($statement);
            if ($prepared === '') {
                continue;
            }

            $ignoreErrors = $this->shouldIgnoreErrors($prepared);
            $ok = $this->database->query($prepared, $ignoreErrors);

            if (! $ok && ! $ignoreErrors) {
                SchemaInstallLogger::logQueryFailure(
                    $this->statementType($prepared),
                    $this->database->getLastError(),
                    $prepared
                );
            }
        }
    }

    private function statementType(string $statement): string
    {
        if (preg_match('/^CREATE\s+TABLE/i', $statement) === 1) {
            return 'CREATE TABLE';
        }

        if (preg_match('/^ALTER\s+TABLE/i', $statement) === 1) {
            return 'ALTER TABLE';
        }

        if (preg_match('/^CREATE\s+(UNIQUE\s+)?INDEX/i', $statement) === 1) {
            return 'CREATE INDEX';
        }

        return 'SQL';
    }

    /**
     * Split DDL on statement terminators. Semicolons inside single-quoted SQL strings are ignored.
     *
     * @return list<string>
     */
    private function parseStatements(string $sql): array
    {
        $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;

        $statements = [];
        $buffer = '';
        $inSingleQuote = false;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];

            if ($char === "'" && ! $inSingleQuote) {
                $inSingleQuote = true;
                $buffer .= $char;
                continue;
            }

            if ($char === "'" && $inSingleQuote) {
                if ($index + 1 < $length && $sql[$index + 1] === "'") {
                    $buffer .= "''";
                    $index++;
                    continue;
                }

                $inSingleQuote = false;
                $buffer .= $char;
                continue;
            }

            if ($char === ';' && ! $inSingleQuote) {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    private function prepareStatement(string $statement): string
    {
        $statement = $this->prefixTableNames($statement);

        if (preg_match('/^CREATE\s+TABLE\s+/i', $statement) === 1) {
            $statement = (string) preg_replace(
                '/^CREATE\s+TABLE\s+/i',
                'CREATE TABLE IF NOT EXISTS ',
                $statement,
                1
            );

            $collate = $this->database->getCharsetCollate();
            if ($collate !== '' && ! str_contains(strtoupper($statement), 'DEFAULT CHARSET')) {
                // Table-level comment uses "COMMENT ="; column comments use "COMMENT '...'" without "=".
                if (preg_match('/\)\s+COMMENT\s*=/i', $statement) === 1) {
                    $statement = (string) preg_replace(
                        '/\)\s+COMMENT\s*=/i',
                        ') ' . $collate . ' COMMENT =',
                        $statement,
                        1
                    );
                } else {
                    $statement = (string) preg_replace(
                        '/\)\s*$/',
                        ') ' . $collate,
                        rtrim($statement)
                    );
                }
            }
        }

        return $statement;
    }

    private function prefixTableNames(string $statement): string
    {
        $tables = $this->tableNamesForReplacement;
        usort(
            $tables,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
        );

        foreach ($tables as $logical) {
            $physical = $this->database->resolveTableName($logical);
            $statement = str_replace(
                '`' . $logical . '`',
                '`' . $physical . '`',
                $statement
            );
        }

        return $statement;
    }

    private function shouldIgnoreErrors(string $statement): bool
    {
        return preg_match(
            '/^\s*ALTER\s+TABLE\s+/i',
            $statement
        ) === 1 || preg_match(
            '/^\s*CREATE\s+(UNIQUE\s+)?INDEX\s+/i',
            $statement
        ) === 1 || preg_match(
            '/^\s*INSERT\s+IGNORE\s+INTO\s+/i',
            $statement
        ) === 1;
    }
}

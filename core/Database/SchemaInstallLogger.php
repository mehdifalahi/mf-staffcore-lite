<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Optional install diagnostics (enable with MF_STAFFCORE_SCHEMA_DEBUG in wp-config.php).
 */
final class SchemaInstallLogger
{
    public static function isEnabled(): bool
    {
        return defined('MF_STAFFCORE_SCHEMA_DEBUG') && MF_STAFFCORE_SCHEMA_DEBUG;
    }

    public static function log(string $message): void
    {
        if (! self::isEnabled()) {
            return;
        }

        error_log('[MF StaffCore Schema] ' . $message);
    }

    public static function logQueryFailure(string $statementType, string $error, string $sqlPreview): void
    {
        if (! self::isEnabled()) {
            return;
        }

        self::log(sprintf(
            '%s failed: %s | SQL: %s',
            $statementType,
            $error,
            substr($sqlPreview, 0, 300)
        ));
    }
}

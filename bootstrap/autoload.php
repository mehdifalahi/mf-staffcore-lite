<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight PSR-4 autoloader when Composer vendor/ is not available.
 */
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'MF\\StaffCore\\Platforms\\WordPress\\' => MF_STAFFCORE_PATH . 'platforms/wordpress/',
        'MF\\StaffCore\\Modules\\' => MF_STAFFCORE_PATH . 'modules/',
        'MF\\StaffCore\\' => MF_STAFFCORE_PATH . 'core/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }

        return;
    }
});

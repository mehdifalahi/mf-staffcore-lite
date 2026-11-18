<?php

/**
 * Plugin Name:       MF StaffCore Lite
 * Plugin URI:        https://mehdifalahi.dev/product/mf-staffcore-wp
 * Description:       HR management for small teams (Lite edition). Upgrade to Pro for more modules.
 * Version:           0.1.13
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Mehdi Falahi
 * Author URI:        https://mehdifalahi.dev
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mf-staffcore-lite
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('MF_STAFFCORE_VERSION', '0.1.13');
define('MF_STAFFCORE_FILE', __FILE__);
define('MF_STAFFCORE_PATH', plugin_dir_path(__FILE__));
define('MF_STAFFCORE_URL', plugin_dir_url(__FILE__));

if (! defined('MF_STAFFCORE_EDITION')) {
    define('MF_STAFFCORE_EDITION', 'free');
}

$composerAutoload = MF_STAFFCORE_PATH . 'vendor/autoload.php';
$fallbackAutoload = MF_STAFFCORE_PATH . 'bootstrap/autoload.php';

if (is_readable($composerAutoload)) {
    require_once $composerAutoload;
} elseif (is_readable($fallbackAutoload)) {
    require_once $fallbackAutoload;
} else {
    return;
}

register_activation_hook(
    MF_STAFFCORE_FILE,
    [\MF\StaffCore\Platforms\WordPress\Database\SchemaLifecycle::class, 'onActivate']
);
register_deactivation_hook(
    MF_STAFFCORE_FILE,
    [\MF\StaffCore\Platforms\WordPress\Database\SchemaLifecycle::class, 'onDeactivate']
);

\MF\StaffCore\Platforms\WordPress\Plugin::boot();

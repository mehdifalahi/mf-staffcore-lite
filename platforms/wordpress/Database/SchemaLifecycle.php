<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Database;

use MF\StaffCore\Database\SchemaInstaller;
use MF\StaffCore\Database\LogicalTables;
use MF\StaffCore\Plan\PlanRegistry;
use MF\StaffCore\Platforms\WordPress\Auth\WordPressPlatformUserGateway;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;

/**
 * WordPress adapter: ensure schema on plugin activation only.
 * Lite build: no Biometric cron or Pro-only schema hooks (classes absent from ZIP).
 */
final class SchemaLifecycle
{
    private const SCHEMA_VERSION_OPTION = 'mf_staffcore_schema_version';

    private static ?SchemaInstaller $installer = null;

    public static function onActivate(): void
    {
        self::installer()->ensureInstalled();
        self::ensureUpgrades();
        ServiceContainer::instance()->permissionService()->ensureAllRoleDefaultsSeeded();
    }

    public static function onDeactivate(): void
    {
    }

    public static function ensureAdministratorStaffUser(): void
    {
        self::registerPlatformUserGatewayIfNeeded();

        ServiceContainer::instance()->staffUserBootstrapSeeder()->ensureAdministratorSeed();
    }

    /**
     * Incremental schema migrations — runs on plugin activation only (not each request).
     */
    private static function ensureUpgrades(): void
    {
        global $wpdb;

        self::repairInvertedForeignKeys();

        $table = $wpdb->prefix . 'mfsc_maintenance';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix, not user input
        $column = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table}` LIKE 'performed_by_employee_id'",
        );

        if ($column === []) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix, not user input
            $wpdb->query(
                "ALTER TABLE `{$table}`
                 ADD COLUMN `performed_by_employee_id` BIGINT NULL
                 COMMENT 'Reference to employee who performed the maintenance'
                 AFTER `performed_by`",
            );
        }

        self::ensureStaffUserTable();
        self::ensureStaffUserManagerScopeModeColumn();
        self::ensureRolePermissionTable();
        self::ensureAdministratorStaffUser();
    }

    private static function repairInvertedForeignKeys(): void
    {
        global $wpdb;

        $gateway = new WordPressDatabaseGateway($wpdb, WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX);
        $repair = new InvertedForeignKeyRepair($wpdb, $gateway);
        $repair->repair();
    }

    private static function ensureStaffUserManagerScopeModeColumn(): void
    {
        global $wpdb;

        $gateway = new WordPressDatabaseGateway($wpdb, WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX);

        if (! $gateway->tableExists('staff_user')) {
            return;
        }

        $table = $wpdb->prefix . 'mfsc_staff_user';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix, not user input
        $column = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table}` LIKE 'manager_scope_mode'",
        );

        if ($column !== []) {
            return;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix, not user input
        $wpdb->query(
            "ALTER TABLE `{$table}`
             ADD COLUMN `manager_scope_mode` ENUM('auto', 'team', 'all') NOT NULL DEFAULT 'auto'
             COMMENT 'Effective manager data scope override; auto = detect from department.manager_id'
             AFTER `role`",
        );
    }

    private static function ensureStaffUserTable(): void
    {
        global $wpdb;

        $gateway = new WordPressDatabaseGateway($wpdb, WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX);

        if ($gateway->tableExists('staff_user')) {
            return;
        }

        self::installer()->ensureInstalled();
    }

    private static function ensureRolePermissionTable(): void
    {
        global $wpdb;

        $gateway = new WordPressDatabaseGateway($wpdb, WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX);

        if ($gateway->tableExists('role_permission')) {
            ServiceContainer::instance()->permissionService()->ensureAllRoleDefaultsSeeded();

            return;
        }

        self::installer()->ensureInstalled();
        ServiceContainer::instance()->permissionService()->ensureAllRoleDefaultsSeeded();
    }

    private static function registerPlatformUserGatewayIfNeeded(): void
    {
        $container = ServiceContainer::instance();

        $container->registerPlatformUserGateway(new WordPressPlatformUserGateway());
    }

    private static function installer(): SchemaInstaller
    {
        if (self::$installer !== null) {
            return self::$installer;
        }

        global $wpdb;

        self::$installer = new SchemaInstaller(
            new WordPressDatabaseGateway($wpdb, WordPressDatabaseGateway::PLUGIN_TABLE_PREFIX),
            new WordPressInstallStateStore(self::SCHEMA_VERSION_OPTION),
            MF_STAFFCORE_PATH . 'database/mf-staffcore-lite-install.sql',
            LogicalTables::ALL,
            LogicalTables::ALL,
        );

        return self::$installer;
    }
}

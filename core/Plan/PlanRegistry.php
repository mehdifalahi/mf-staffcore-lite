<?php

declare(strict_types=1);

namespace MF\StaffCore\Plan;

/**
 * Lite-safe plan context and upgrade messaging.
 *
 * Pro modules are physically absent from the Lite build. Returning false from
 * canUseModule() for those IDs is not trialware — the code is not shipped in
 * this package (WordPress.org Guideline 5).
 */
final class PlanRegistry
{
    public const UPGRADE_PRODUCT_URL = 'https://mehdifalahi.dev/product/mf-staffcore-wp';

    /**
     * Modules not included in the Lite ZIP (Pro-only add-ons).
     *
     * @var list<string>
     */
    public const PRO_MODULE_IDS = [
        'biometric_devices',
        'documents',
        'company_awards',
        'employee_awards',
        'kpi_categories',
        'kpi_metrics',
        'performance_reviews',
        'kpi_employees',
        'backup_restore',
    ];

    private static ?PlanContext $context = null;

    public static function setContext(PlanContext $context): void
    {
        self::$context = $context;
    }

    public static function isPro(): bool
    {
        return self::context()->isPro();
    }

    public static function isFree(): bool
    {
        return ! self::isPro();
    }

    public static function shouldShowUpgradeUi(): bool
    {
        return self::isFree();
    }

    public static function upgradeUrl(?string $utmContent = null): string
    {
        $query = [
            'utm_source' => 'staffcore_lite',
            'utm_medium' => 'plugin',
            'utm_campaign' => 'upgrade',
        ];

        if ($utmContent !== null && $utmContent !== '') {
            $query['utm_content'] = $utmContent;
        }

        return self::UPGRADE_PRODUCT_URL . '?' . http_build_query($query);
    }

    public static function canUseModule(string $moduleId): bool
    {
        if ($moduleId === '') {
            return true;
        }

        return ! in_array($moduleId, self::PRO_MODULE_IDS, true);
    }

    public static function canBulkLink(): bool
    {
        return true;
    }

    public static function assertCanBulkLink(): void
    {
    }

    public static function assertCanUseModule(string $moduleId): void
    {
    }

    public static function isRouteAllowed(string $menuId, ?string $tabSlug): bool
    {
        return true;
    }

    /**
     * @param list<array<string, mixed>> $menus
     * @return list<array<string, mixed>>
     */
    public static function filterMenus(array $menus): array
    {
        return $menus;
    }

    public static function upgradeMessage(): string
    {
        return 'Explore additional modules in MF StaffCore Pro.';
    }

    public static function editionLabel(): string
    {
        return self::isPro() ? 'Pro' : 'Lite';
    }

    private static function context(): PlanContext
    {
        if (self::$context === null) {
            self::$context = new DevelopmentPlanContext();
        }

        return self::$context;
    }
}

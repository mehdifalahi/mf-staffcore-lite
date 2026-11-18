<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\AuthorizationService;
use MF\StaffCore\Modules\PerformanceOverview\DTO\PerformanceOverviewStats;
use MF\StaffCore\Modules\PerformanceOverview\PerformanceStatCards;
use MF\StaffCore\Navigation\AdminMenuRegistry;
use MF\StaffCore\Navigation\AdminPageResolver;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Navigation\AuthorizedMenuRegistry;
use MF\StaffCore\Navigation\EmployeeSidebarMenuRegistry;
use MF\StaffCore\Plan\PlanRegistry;
use MF\StaffCore\Platforms\WordPress\Auth\WordPressAuthorizationBridge;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Platforms\WordPress\I18n\UiLabelTranslator;
use MF\StaffCore\Authorization\RoleRegistry;

final class PageRenderer
{
    private const ROUTE_ALLOWED = 0;

    private const ROUTE_DENIED = 1;

    public function __construct(
        private readonly AdminPageResolver $resolver,
        private readonly AdminUrlBuilder $urls,
    ) {
    }

    /**
     * Resolve a redirect URL for forbidden routes before any HTML output (frontend panel preflight).
     * Returns null when the request may proceed to {@see render()}.
     */
    public function resolvePreflightRedirect(): ?string
    {
        if (! is_user_logged_in()) {
            return null;
        }

        $this->authorizationBridge()->resolveCurrentStaffUser();
        $auth = $this->authorization();
        $staffUser = $auth->current();

        if ($staffUser === null || ! $staffUser->isActive()) {
            return null;
        }

        [$menuId, $tabSlug, $isDefaultRouteRequest] = $this->resolveRequestRoute();

        if ($isDefaultRouteRequest) {
            $defaultRoute = $this->resolveDefaultRouteForUser($auth);
            $menuId = $defaultRoute->menuId;
            $tabSlug = $defaultRoute->tabSlug;
        }

        $url = $this->resolveRouteRedirectUrl($menuId, $tabSlug, $auth);

        if ($url !== null) {
            return $url;
        }

        $context = $this->resolver->resolve($menuId, $tabSlug);

        if ($auth->canAccessRoute($context->menuId, $context->tabSlug)) {
            return null;
        }

        return $this->resolveRouteRedirectUrl($context->menuId, $context->tabSlug, $auth);
    }

    public function render(): void
    {
        if (! is_user_logged_in()) {
            $this->renderAccessDenied(
                __('You must be logged in to access StaffCore.', 'mf-staffcore-lite'),
            );

            return;
        }

        $bridge = $this->authorizationBridge();
        $auth = $this->authorization();
        $bridge->resolveCurrentStaffUser();

        $staffUser = $auth->current();

        if ($staffUser === null || ! $staffUser->isActive()) {
            $this->renderAccessDenied(
                __(
                    'You do not have access to StaffCore. Ask an administrator to add your account under Settings → Users.',
                    'mf-staffcore-lite',
                ),
            );

            return;
        }

        [$menuId, $tabSlug, $isDefaultRouteRequest] = $this->resolveRequestRoute();

        if ($isDefaultRouteRequest) {
            $defaultRoute = $this->resolveDefaultRouteForUser($auth);
            $menuId = $defaultRoute->menuId;
            $tabSlug = $defaultRoute->tabSlug;
        }

        $routeAccess = $this->evaluateRouteAccess($menuId, $tabSlug, $auth);

        if ($routeAccess === self::ROUTE_DENIED) {
            $can = static fn (string $permission): bool => $auth->can($permission);
            $hasAnyRoute = AuthorizedMenuRegistry::firstAccessibleRoute($can) !== null;
            $this->renderAccessDenied(
                $hasAnyRoute
                    ? __('You do not have permission to view this page.', 'mf-staffcore-lite')
                    : __('No StaffCore pages are available for your role.', 'mf-staffcore-lite'),
            );

            return;
        }

        $context = $this->resolver->resolve($menuId, $tabSlug);

        if (! $auth->canAccessRoute($context->menuId, $context->tabSlug)) {
            if ($this->evaluateRouteAccess($context->menuId, $context->tabSlug, $auth) === self::ROUTE_DENIED) {
                $this->renderAccessDenied(
                    __('You do not have permission to view this page.', 'mf-staffcore-lite'),
                );
            }

            return;
        }

        $can = static fn (string $permission): bool => $auth->can($permission);
        $isEmployee = $auth->current()?->role === \MF\StaffCore\Authorization\RoleRegistry::ROLE_EMPLOYEE;
        $menus = AuthorizedMenuRegistry::filterMenus(AdminMenuRegistry::menus(), $can);
        $menus = PlanRegistry::filterMenus($menus);
        $menus = AuthorizedMenuRegistry::filterLineManagerTabs(
            $menus,
            ServiceContainer::instance()->isLineManager(),
        );
        $menus = EmployeeSidebarMenuRegistry::apply($menus, $can, $isEmployee);
        $homeRoute = AuthorizedMenuRegistry::firstAccessibleRoute($can)
            ?? new AdminRoute('dashboard', null);

        $contentView = MF_STAFFCORE_PATH . $context->contentView;

        if ($this->isStandalonePayrollPayslipRequest($context->menuId, $context->tabSlug)) {
            $this->renderStandaloneContentDocument($contentView, $context->pageTitle);

            return;
        }

        $dashboardUrl = $this->urls->build($homeRoute);
        $activeMenuId = $context->menuId;
        $activeTabSlug = $context->tabSlug;
        $pageTitle = EmployeeSidebarMenuRegistry::pageTitleForEmployee(
            $context->menuId,
            $context->tabSlug,
            $context->pageTitle,
            $isEmployee,
        );
        $pageDescription = EmployeeSidebarMenuRegistry::pageDescriptionForEmployee(
            $context->menuId,
            $context->tabSlug,
            $context->pageDescription,
            $isEmployee,
        );
        $currentMenu = AuthorizedMenuRegistry::findMenuInList($menus, $activeMenuId) ?? $context->menu;
        $menuTabs = $context->hasTabs() && ! EmployeeSidebarMenuRegistry::shouldHideHorizontalTabs($activeMenuId, $isEmployee)
            ? ($currentMenu['tabs'] ?? [])
            : [];
        $pageHeaderIcon = $context->tab !== null
            ? (string) ($context->tab['icon'] ?? 'circle')
            : (string) ($context->menu['icon'] ?? 'circle');
        $urlBuilder = $this->urls;
        $resolver = $this->resolver;
        $wordpressAdminUrl = admin_url();
        $showUpgradeUi = PlanRegistry::shouldShowUpgradeUi() && ! $isEmployee;
        $editionLabel = PlanRegistry::editionLabel();
        $sidebarUpgradeUrl = PlanRegistry::upgradeUrl('sidebar');

        /** @var list<array{label: string, value: int, icon: string, tone: string}> $statCards */
        $statCards = [];

        if ($activeMenuId === 'performance' && PlanRegistry::canUseModule('performance_reviews')) {
            $container = ServiceContainer::instance();

            if (! ($isEmployee && $activeTabSlug === 'employee-kpis')) {
                try {
                    $overviewStats = $container->performanceOverview()->stats(
                        $container->performanceScopeEmployeeIds(),
                    );
                } catch (AuthorizationException) {
                    $overviewStats = new PerformanceOverviewStats();
                }

                $statCards = PerformanceStatCards::build(
                    $overviewStats,
                    [UiLabelTranslator::class, 'translate'],
                );
            }
        }

        echo '<div class="wrap mf-staffcore-wrap">';
        include MF_STAFFCORE_PATH . 'ui/layouts/admin-shell.php';
        echo '</div>';
    }

    /**
     * @return self::ROUTE_*
     */
    private function evaluateRouteAccess(
        string $menuId,
        ?string $tabSlug,
        AuthorizationService $auth,
    ): int {
        if ($auth->canAccessRoute($menuId, $tabSlug)) {
            return self::ROUTE_ALLOWED;
        }

        $url = $this->resolveRouteRedirectUrl($menuId, $tabSlug, $auth);

        if ($url !== null) {
            if (! headers_sent()) {
                wp_safe_redirect($url);
                exit;
            }

            return self::ROUTE_DENIED;
        }

        return self::ROUTE_DENIED;
    }

    private function resolveRouteRedirectUrl(
        string $menuId,
        ?string $tabSlug,
        AuthorizationService $auth,
    ): ?string {
        if ($auth->canAccessRoute($menuId, $tabSlug)) {
            return null;
        }

        $can = static fn (string $permission): bool => $auth->can($permission);
        $tabInMenu = AuthorizedMenuRegistry::firstAccessibleTabSlugInMenu($menuId, $can);

        if ($tabInMenu !== null && $tabInMenu !== ($tabSlug ?? '')) {
            return $this->urls->build(new AdminRoute($menuId, $tabInMenu));
        }

        $fallback = AuthorizedMenuRegistry::firstAccessibleRoute($can);

        if ($fallback === null) {
            return null;
        }

        if (
            $fallback->menuId === $menuId
            && ($fallback->tabSlug ?? '') === ($tabSlug ?? '')
        ) {
            return null;
        }

        return $this->urls->build($fallback);
    }

    /**
     * @return array{0: string, 1: ?string, 2: bool}
     */
    private function resolveRequestRoute(): array
    {
        $hasMenu = isset($_GET[AdminPageResolver::QUERY_MENU]);
        $menuId = $hasMenu
            ? sanitize_key((string) $_GET[AdminPageResolver::QUERY_MENU])
            : 'dashboard';

        $tabSlug = isset($_GET[AdminPageResolver::QUERY_TAB])
            ? sanitize_key((string) $_GET[AdminPageResolver::QUERY_TAB])
            : null;

        if ($tabSlug === '') {
            $tabSlug = null;
        }

        return [$menuId, $tabSlug, ! $hasMenu];
    }

    private function resolveDefaultRouteForUser(AuthorizationService $auth): AdminRoute
    {
        $can = static fn (string $permission): bool => $auth->can($permission);

        return AuthorizedMenuRegistry::firstAccessibleRoute($can)
            ?? new AdminRoute('dashboard', null);
    }

    private function renderAccessDenied(string $message): void
    {
        $auth = $this->authorization();
        $can = static fn (string $permission): bool => $auth->can($permission);
        $isEmployee = $auth->current()?->role === \MF\StaffCore\Authorization\RoleRegistry::ROLE_EMPLOYEE;
        $menus = AuthorizedMenuRegistry::filterMenus(AdminMenuRegistry::menus(), $can);
        $menus = PlanRegistry::filterMenus($menus);
        $menus = AuthorizedMenuRegistry::filterLineManagerTabs(
            $menus,
            ServiceContainer::instance()->isLineManager(),
        );
        $menus = EmployeeSidebarMenuRegistry::apply($menus, $can, $isEmployee);
        $homeRoute = AuthorizedMenuRegistry::firstAccessibleRoute($can)
            ?? new AdminRoute('dashboard', null);
        $menuTabs = [];
        $accessDeniedMessage = $message;
        $contentView = MF_STAFFCORE_PATH . 'ui/components/access-denied.php';
        $dashboardUrl = $this->urls->build($homeRoute);
        $activeMenuId = '';
        $activeTabSlug = null;
        $pageTitle = __('Access denied', 'mf-staffcore-lite');
        $pageDescription = '';
        $pageHeaderIcon = 'lock';
        $urlBuilder = $this->urls;
        $resolver = $this->resolver;
        $wordpressAdminUrl = admin_url();
        $showUpgradeUi = PlanRegistry::shouldShowUpgradeUi() && ! $isEmployee;
        $editionLabel = PlanRegistry::editionLabel();
        $sidebarUpgradeUrl = PlanRegistry::upgradeUrl('sidebar');
        $statCards = [];

        echo '<div class="wrap mf-staffcore-wrap">';
        include MF_STAFFCORE_PATH . 'ui/layouts/admin-shell.php';
        echo '</div>';
    }

    private function authorizationBridge(): WordPressAuthorizationBridge
    {
        return ServiceContainer::instance()->authorizationBridge();
    }

    private function authorization(): AuthorizationService
    {
        return ServiceContainer::instance()->authorization();
    }

    private function isStandalonePayrollPayslipRequest(string $menuId, ?string $tabSlug): bool
    {
        if ($menuId !== 'hr-management') {
            return false;
        }

        if (! in_array($tabSlug, ['payroll', 'my-payslips'], true)) {
            return false;
        }

        $view = isset($_GET['sc_view'])
            ? sanitize_key(wp_unslash((string) $_GET['sc_view']))
            : '';

        return $view === 'payslip';
    }

    private function renderStandaloneContentDocument(string $contentView, string $title): void
    {
        if (! is_readable($contentView)) {
            echo '<div class="notice notice-warning"><p>';
            esc_html_e('Page view not found.', 'mf-staffcore-lite');
            echo '</p></div>';

            return;
        }

        ob_start();
        include $contentView;
        $contentHtml = (string) ob_get_clean();

        echo '<!doctype html>';
        echo '<html lang="' . esc_attr(get_locale()) . '">';
        echo '<head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($title) . '</title>';
        echo '</head>';
        echo '<body class="staffcore-standalone-payslip">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- payslip view HTML is escaped at echo sites
        echo $contentHtml;
        echo '</body>';
        echo '</html>';
    }
}

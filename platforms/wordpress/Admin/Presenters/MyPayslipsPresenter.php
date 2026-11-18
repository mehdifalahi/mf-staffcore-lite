<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\MyPayslips\EmployeePayslipStatCards;
use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;

final class MyPayslipsPresenter
{
    public function render(): void
    {
        $container = ServiceContainer::instance();
        $auth = $container->authorization();
        $context = $auth->current();

        if ($context === null || $context->role !== RoleRegistry::ROLE_EMPLOYEE) {
            echo '<div class="alert alert-warning" role="alert">';
            esc_html_e('My Payslips is only available for employees.', 'mf-staffcore-lite');
            echo '</div>';

            return;
        }

        $urlBuilder = new AdminUrlBuilder();
        $payslipView = isset($_GET['sc_view']) ? sanitize_key((string) $_GET['sc_view']) : '';

        if ($payslipView === 'payslip') {
            $this->renderPayslip($container, $context->platformUserId);

            return;
        }

        $scopeError = null;
        $employeeId = null;

        try {
            $scopeIds = $container->scopeEmployeeIdsForModule('my_payslips');
            $employeeId = $scopeIds[0] ?? null;
        } catch (AuthorizationException $exception) {
            $scopeError = $exception->getMessage();
        }

        $service = $container->myPayslips();
        $pagination = new ListTablePagination($urlBuilder);
        $listFilter = self::resolveListFilter();
        $filterRoute = new AdminRoute('hr-management', 'my-payslips');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $filterQuery = $listFilter->toQueryArgs();

        if ($scopeError === null && $employeeId !== null) {
            $page = ListTablePagination::resolvePage();
            $perPage = ListTablePagination::perPage();
            $result = $service->listPageForEmployee($employeeId, $page, $perPage, $listFilter);

            $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
            if ($page !== $result->page) {
                $result = $service->listPageForEmployee($employeeId, $page, $perPage, $listFilter);
            }

            $payslips = $result->items;
            $currentPage = $result->page;
            $totalPages = $result->totalPages();
            $totalItems = $result->total;
        } else {
            $payslips = [];
            $currentPage = 1;
            $totalPages = 1;
            $totalItems = 0;
            $perPage = ListTablePagination::perPage();
        }

        $payslipBaseUrl = self::payslipBaseUrl($urlBuilder);
        $payslipUrls = [];

        foreach ($payslips as $payslip) {
            $payslipUrls['payslip_' . $payslip->id] = self::payslipUrl($urlBuilder, $payslip->id);
        }

        $company = $container->companyInformation()->getProfile();
        $currencyCode = $company?->currency;
        $currentYear = (int) gmdate('Y');
        $summary = ($scopeError === null && $employeeId !== null)
            ? $service->employeeSummary($employeeId, $currentYear)
            : null;
        $statCards = $summary !== null
            ? EmployeePayslipStatCards::build($summary, $currencyCode)
            : [];
        $availableYears = ($scopeError === null && $employeeId !== null)
            ? $service->distinctYearsForEmployee($employeeId)
            : [];

        include MF_STAFFCORE_PATH . 'modules/MyPayslips/Views/page.php';
    }

    public static function resolveListFilter(): PayrollListFilter
    {
        $status = isset($_GET[PayrollListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[PayrollListFilter::QUERY_STATUS]))
            : '';
        $year = isset($_GET[PayrollListFilter::QUERY_YEAR])
            ? (int) $_GET[PayrollListFilter::QUERY_YEAR]
            : 0;
        $month = isset($_GET[PayrollListFilter::QUERY_MONTH])
            ? (int) $_GET[PayrollListFilter::QUERY_MONTH]
            : 0;

        return new PayrollListFilter(
            paymentStatus: $status !== '' && $status !== 'all' ? strtoupper($status) : null,
            year: $year >= 2000 && $year <= 2100 ? $year : null,
            month: $month >= 1 && $month <= 12 ? $month : null,
        );
    }

    public static function payslipUrl(AdminUrlBuilder $urlBuilder, int $payrollId): string
    {
        return $urlBuilder->build(
            new AdminRoute('hr-management', 'my-payslips'),
            [
                'sc_view' => 'payslip',
                'payroll_id' => $payrollId,
            ],
        );
    }

    public static function payslipBaseUrl(AdminUrlBuilder $urlBuilder): string
    {
        return $urlBuilder->build(
            new AdminRoute('hr-management', 'my-payslips'),
            ['sc_view' => 'payslip'],
        );
    }

    public static function formatPaymentDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(date_i18n('M j, Y', $timestamp));
    }

    /**
     * @param list<int> $availableYears
     */
    public static function yearFilterOptions(array $availableYears, ?int $selectedYear): void
    {
        $showAllYears = $selectedYear === null || $selectedYear <= 0;

        printf(
            '<option value=""%s>%s</option>',
            $showAllYears ? ' selected="selected"' : '',
            esc_html__('All Years', 'mf-staffcore-lite'),
        );

        $currentYear = (int) gmdate('Y');
        $years = [];

        for ($year = $currentYear - 5; $year <= $currentYear + 1; ++$year) {
            $years[$year] = true;
        }

        foreach ($availableYears as $year) {
            $year = (int) $year;

            if ($year > 0) {
                $years[$year] = true;
            }
        }

        krsort($years, SORT_NUMERIC);

        foreach (array_keys($years) as $year) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) $year),
                selected($selectedYear, $year, false),
                esc_html((string) $year),
            );
        }
    }

    public static function paymentStatusFilterOptions(?string $selected): void
    {
        $options = [
            'all' => __('All Status', 'mf-staffcore-lite'),
            \MF\StaffCore\Modules\Payroll\DTO\Payroll::STATUS_PENDING => __('Pending', 'mf-staffcore-lite'),
            \MF\StaffCore\Modules\Payroll\DTO\Payroll::STATUS_PROCESSED => __('Processed', 'mf-staffcore-lite'),
            \MF\StaffCore\Modules\Payroll\DTO\Payroll::STATUS_PAID => __('Paid', 'mf-staffcore-lite'),
            \MF\StaffCore\Modules\Payroll\DTO\Payroll::STATUS_ON_HOLD => __('On hold', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($selected ?? 'all', $value, false),
                esc_html($label),
            );
        }
    }

    public static function formatDeductionAmount(float $amount, ?string $currencyCode): string
    {
        $formatted = PayrollPresenter::formatMoneyWithCurrency($amount, $currencyCode);

        return '<span class="text-danger">-' . $formatted . '</span>';
    }

    public static function formatNetSalaryAmount(float $amount, ?string $currencyCode): string
    {
        return '<span class="text-success fw-medium">'
            . PayrollPresenter::formatMoneyWithCurrency($amount, $currencyCode)
            . '</span>';
    }

    private function renderPayslip(ServiceContainer $container, int $platformUserId): void
    {
        $employeeId = $container->myPayslips()->linkedEmployeeId($platformUserId);
        $payrollId = isset($_GET['payroll_id']) ? (int) $_GET['payroll_id'] : 0;
        $payroll = null;

        if ($employeeId !== null && $employeeId > 0 && $payrollId > 0) {
            $payroll = $container->myPayslips()->findPayslipForEmployee($payrollId, $employeeId);
        }

        $company = $container->companyInformation()->getProfile();
        $employee = $payroll !== null ? $container->employees()->get($payroll->employeeId) : null;
        $currencyCode = $company?->currency;

        include MF_STAFFCORE_PATH . 'modules/Payroll/Views/payslip.php';
    }
}

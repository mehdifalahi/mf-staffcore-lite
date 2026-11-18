<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformation;
use MF\StaffCore\Modules\Payroll\DTO\Payroll;
use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\PayrollAjaxHandler;

final class PayrollPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->payroll();
        $payslipView = isset($_GET['sc_view']) ? sanitize_key((string) $_GET['sc_view']) : '';

        if ($payslipView === 'payslip') {
            $this->renderPayslip($container, $service);

            return;
        }

        $scopeEmployeeIds = null;
        $scopeError = null;

        try {
            $scopeEmployeeIds = $container->scopeEmployeeIdsForModule('payroll');
        } catch (AuthorizationException $exception) {
            $scopeError = $exception->getMessage();
        }

        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $listFilter = self::resolveListFilter($scopeEmployeeIds);
        $filterQuery = $listFilter->toQueryArgs();

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();

        if ($scopeError === null) {
            $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);

            $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
            if ($page !== $result->page) {
                $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);
            }

            $payrollRecords = $result->items;
            $currentPage = $result->page;
            $totalPages = $result->totalPages();
            $totalItems = $result->total;
        } else {
            $payrollRecords = [];
            $currentPage = 1;
            $totalPages = 1;
            $totalItems = 0;
        }

        $perPage = $perPage;
        $employees = $scopeError === null ? $service->employeeOptions($scopeEmployeeIds) : [];
        $filterRoute = new AdminRoute('hr-management', 'payroll');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = PayrollAjaxHandler::nonce();
        $scopeError = $scopeError ?? null;
        $perms = $container->authorization()->modulePermissions('payroll');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $showRowViewActions = true;
        $bulkDeletePermission = 'payroll.delete';
        $bulkDeleteModalId = 'staffcore-payroll-bulk-delete-modal';
        $bulkEntityPlural = __('payroll records', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_payroll_bulk_delete';
        $payslipBaseUrl = self::payslipBaseUrl();

        include MF_STAFFCORE_PATH . 'modules/Payroll/Views/page.php';
    }

    public static function resolveListFilter(?array $scopeEmployeeIds = null): PayrollListFilter
    {
        $employeeId = isset($_GET[PayrollListFilter::QUERY_EMPLOYEE])
            ? (int) $_GET[PayrollListFilter::QUERY_EMPLOYEE]
            : 0;
        $status = isset($_GET[PayrollListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[PayrollListFilter::QUERY_STATUS]))
            : '';
        $year = isset($_GET[PayrollListFilter::QUERY_YEAR])
            ? (int) $_GET[PayrollListFilter::QUERY_YEAR]
            : 0;
        $month = isset($_GET[PayrollListFilter::QUERY_MONTH])
            ? (int) $_GET[PayrollListFilter::QUERY_MONTH]
            : 0;

        if ($scopeEmployeeIds !== null && count($scopeEmployeeIds) === 1) {
            $employeeId = $scopeEmployeeIds[0];
        }

        return new PayrollListFilter(
            employeeId: $employeeId > 0 ? $employeeId : null,
            paymentStatus: $status !== '' && $status !== 'all' ? strtoupper($status) : null,
            year: $year >= 2000 && $year <= 2100 ? $year : null,
            month: $month >= 1 && $month <= 12 ? $month : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function formatMoney(float $amount): string
    {
        return esc_html(number_format($amount, 2));
    }

    public static function formatMoneyWithCurrency(float $amount, ?string $currencyCode): string
    {
        $symbol = self::currencySymbol($currencyCode);

        return esc_html($symbol . ' ' . number_format($amount, 2));
    }

    public static function periodLabel(int $month, int $year): string
    {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);

        if ($timestamp === false) {
            return esc_html($month . '/' . $year);
        }

        return esc_html(date_i18n('F Y', $timestamp));
    }

    public static function payslipUrl(int $payrollId): string
    {
        return add_query_arg(
            [
                'page' => 'mf-staffcore-lite',
                'sc_menu' => 'hr-management',
                'sc_tab' => 'payroll',
                'sc_view' => 'payslip',
                'payroll_id' => $payrollId,
            ],
            admin_url('admin.php'),
        );
    }

    public static function payslipBaseUrl(): string
    {
        return add_query_arg(
            [
                'page' => 'mf-staffcore-lite',
                'sc_menu' => 'hr-management',
                'sc_tab' => 'payroll',
                'sc_view' => 'payslip',
            ],
            admin_url('admin.php'),
        );
    }

    public static function paymentMethodLabel(?string $method): string
    {
        if ($method === null || $method === '') {
            return '—';
        }

        return match ($method) {
            Payroll::METHOD_BANK => esc_html__('Bank transfer', 'mf-staffcore-lite'),
            Payroll::METHOD_CASH => esc_html__('Cash', 'mf-staffcore-lite'),
            Payroll::METHOD_CHEQUE => esc_html__('Cheque', 'mf-staffcore-lite'),
            default => esc_html($method),
        };
    }

    public static function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            Payroll::STATUS_PENDING => esc_html__('Pending', 'mf-staffcore-lite'),
            Payroll::STATUS_PROCESSED => esc_html__('Processed', 'mf-staffcore-lite'),
            Payroll::STATUS_PAID => esc_html__('Paid', 'mf-staffcore-lite'),
            Payroll::STATUS_ON_HOLD => esc_html__('On hold', 'mf-staffcore-lite'),
            default => esc_html($status),
        };
    }

    /**
     * @return list<string>
     */
    public static function companyMetaLines(?CompanyInformation $company): array
    {
        if ($company === null) {
            return [];
        }

        $lines = [];

        if ($company->address !== null && $company->address !== '') {
            $lines[] = $company->address;
        }

        $cityLine = trim(implode(', ', array_filter([
            $company->city,
            $company->province,
            $company->country,
        ])));

        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }

        if ($company->phone !== null && $company->phone !== '') {
            /* translators: %s: company phone number */
            $lines[] = sprintf(__('Phone: %s', 'mf-staffcore-lite'), $company->phone);
        }

        if ($company->email !== null && $company->email !== '') {
            /* translators: %s: company email address */
            $lines[] = sprintf(__('Email: %s', 'mf-staffcore-lite'), $company->email);
        }

        return $lines;
    }

    private static function currencySymbol(?string $currencyCode): string
    {
        return match (strtoupper((string) $currencyCode)) {
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            'IRR' => 'IRR',
            'AED' => 'AED',
            'SAR' => 'SAR',
            'TRY' => 'TRY',
            'INR' => 'INR',
            'PKR' => 'PKR',
            default => '$',
        };
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            Payroll::STATUS_PENDING => '<span class="badge bg-warning-lt">'
                . esc_html__('Pending', 'mf-staffcore-lite') . '</span>',
            Payroll::STATUS_PROCESSED => '<span class="badge bg-azure-lt">'
                . esc_html__('Processed', 'mf-staffcore-lite') . '</span>',
            Payroll::STATUS_PAID => '<span class="badge bg-success-lt">'
                . esc_html__('Paid', 'mf-staffcore-lite') . '</span>',
            Payroll::STATUS_ON_HOLD => '<span class="badge bg-secondary-lt">'
                . esc_html__('On hold', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($status) . '</span>',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function payrollPayload(Payroll $payroll): array
    {
        return $payroll->toArray();
    }

    public static function monthOptions(?int $selectedMonth = null): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $timestamp = mktime(0, 0, 0, $month, 1, (int) gmdate('Y'));
            $label = $timestamp !== false ? date_i18n('F', $timestamp) : (string) $month;

            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) $month),
                selected($selectedMonth, $month, false),
                esc_html($label),
            );
        }
    }

    public static function paymentStatusOptions(): void
    {
        $options = [
            Payroll::STATUS_PENDING => __('Pending', 'mf-staffcore-lite'),
            Payroll::STATUS_PROCESSED => __('Processed', 'mf-staffcore-lite'),
            Payroll::STATUS_PAID => __('Paid', 'mf-staffcore-lite'),
            Payroll::STATUS_ON_HOLD => __('On hold', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label),
            );
        }
    }

    public static function paymentStatusFilterOptions(?string $selected): void
    {
        $options = [
            'all' => __('All Statuses', 'mf-staffcore-lite'),
            Payroll::STATUS_PENDING => __('Pending', 'mf-staffcore-lite'),
            Payroll::STATUS_PROCESSED => __('Processed', 'mf-staffcore-lite'),
            Payroll::STATUS_PAID => __('Paid', 'mf-staffcore-lite'),
            Payroll::STATUS_ON_HOLD => __('On hold', 'mf-staffcore-lite'),
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

    public static function paymentMethodOptions(): void
    {
        $options = [
            '' => __('Not set', 'mf-staffcore-lite'),
            Payroll::METHOD_BANK => __('Bank transfer', 'mf-staffcore-lite'),
            Payroll::METHOD_CASH => __('Cash', 'mf-staffcore-lite'),
            Payroll::METHOD_CHEQUE => __('Cheque', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label),
            );
        }
    }

    /**
     * @param list<array{id: int, label: string}> $employees
     */
    public static function employeeOptions(array $employees, ?int $selectedId = null): void
    {
        foreach ($employees as $employee) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) (int) $employee['id']),
                selected($selectedId, (int) $employee['id'], false),
                esc_html($employee['label']),
            );
        }
    }

    private function renderPayslip(
        \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer $container,
        \MF\StaffCore\Modules\Payroll\Services\PayrollService $service,
    ): void {
        $payrollId = isset($_GET['payroll_id']) ? (int) $_GET['payroll_id'] : 0;
        $payroll = null;
        $scopeEmployeeIds = null;

        try {
            $scopeEmployeeIds = $container->scopeEmployeeIdsForModule('payroll');
        } catch (AuthorizationException) {
            $scopeEmployeeIds = [];
        }

        if ($payrollId > 0 && $scopeEmployeeIds !== []) {
            $payroll = $service->findById($payrollId, $scopeEmployeeIds);
        }

        $company = $container->companyInformation()->getProfile();
        $employee = $payroll !== null ? $container->employees()->get($payroll->employeeId) : null;
        $currencyCode = $company?->currency;

        include MF_STAFFCORE_PATH . 'modules/Payroll/Views/payslip.php';
    }
}

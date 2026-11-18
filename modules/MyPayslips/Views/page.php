<?php

declare(strict_types=1);

/**
 * @var list<\MF\StaffCore\Modules\Payroll\DTO\Payroll> $payslips
 * @var list<array{label: string, value: int|string, icon: string, tone: string, hint?: string, hintTone?: string}> $statCards
 * @var \MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter $listFilter
 * @var array<string, scalar> $filterQuery
 * @var string $filterActionUrl
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var \MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination $pagination
 * @var string|null $scopeError
 * @var array<string, string> $payslipUrls
 * @var string|null $currencyCode
 * @var list<int> $availableYears
 */

use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\MyPayslipsPresenter;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PayrollPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-my-payslips">
    <?php if (! empty($scopeError)) : ?>
        <div class="alert alert-warning mb-3" role="alert"><?php echo esc_html($scopeError); ?></div>
    <?php endif; ?>

    <?php
    $statCardsRowClass = 'staffcore-stat-cards--three';
    $statCardsColumnClass = 'col-md-4';
    include MF_STAFFCORE_PATH . 'ui/components/stat-cards-outline.php';
    ?>

    <div class="card mt-3">
        <div class="card-header staffcore-card-header-with-filters">
            <h3 class="card-title mb-0"><?php esc_html_e('Payslip History', 'mf-staffcore-lite'); ?></h3>
            <div class="card-actions">
                <form method="get" action="<?php echo esc_url($filterActionUrl); ?>" class="staffcore-inline-filter-form">
                    <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-route-fields.php'; ?>
                    <select
                        class="form-select"
                        id="my-payslip-filter-year"
                        name="<?php echo esc_attr(PayrollListFilter::QUERY_YEAR); ?>"
                        aria-label="<?php esc_attr_e('Year', 'mf-staffcore-lite'); ?>"
                        data-staffcore-search="off"
                        onchange="this.form.submit()"
                    >
                        <?php MyPayslipsPresenter::yearFilterOptions($availableYears, $listFilter->year); ?>
                    </select>
                    <select
                        class="form-select"
                        id="my-payslip-filter-status"
                        name="<?php echo esc_attr(PayrollListFilter::QUERY_STATUS); ?>"
                        aria-label="<?php esc_attr_e('Status', 'mf-staffcore-lite'); ?>"
                        data-staffcore-search="off"
                        onchange="this.form.submit()"
                    >
                        <?php MyPayslipsPresenter::paymentStatusFilterOptions($listFilter->paymentStatus ?? 'all'); ?>
                    </select>
                    <?php include MF_STAFFCORE_PATH . 'ui/components/list-filter-reset-link.php'; ?>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Period', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Payment Date', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Gross Salary', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Deductions', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Net Salary', 'mf-staffcore-lite'); ?></th>
                        <th><?php esc_html_e('Status', 'mf-staffcore-lite'); ?></th>
                        <th class="w-1"><?php esc_html_e('Actions', 'mf-staffcore-lite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payslips === []) : ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-secondary mb-2">
                                    <?php TablerIcon::print('file-invoice', 'icon-lg'); ?>
                                </div>
                                <div class="fw-medium mb-1"><?php esc_html_e('No payslips found', 'mf-staffcore-lite'); ?></div>
                                <div class="text-secondary small">
                                    <?php esc_html_e('Your payslips will appear here once payroll is processed.', 'mf-staffcore-lite'); ?>
                                </div>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($payslips as $payslip) : ?>
                            <tr>
                                <td>
                                    <div class="fw-medium">
                                        <?php echo wp_kses_post(PayrollPresenter::periodLabel($payslip->payrollMonth, $payslip->payrollYear)); ?>
                                    </div>
                                    <div class="text-secondary small">
                                        <?php
                                        $presentDays = (int) $payslip->presentDays;
                                        echo esc_html(
                                            sprintf(
                                                /* translators: %d: number of days the employee was present */
                                                _n(
                                                    '%d Day Worked',
                                                    '%d Days Worked',
                                                    $presentDays,
                                                    'mf-staffcore-lite'
                                                ),
                                                $presentDays
                                            )
                                        );
                                        ?>
                                    </div>
                                </td>
                                <td class="text-secondary">
                                    <?php echo wp_kses_post(MyPayslipsPresenter::formatPaymentDate($payslip->paymentDate)); ?>
                                </td>
                                <td>
                                    <?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payslip->grossPay(), $currencyCode)); ?>
                                </td>
                                <td>
                                    <?php echo wp_kses_post(MyPayslipsPresenter::formatDeductionAmount($payslip->totalDeductions(), $currencyCode)); ?>
                                </td>
                                <td>
                                    <?php echo wp_kses_post(MyPayslipsPresenter::formatNetSalaryAmount($payslip->netPay(), $currencyCode)); ?>
                                </td>
                                <td><?php echo wp_kses_post(PayrollPresenter::statusBadge($payslip->paymentStatus)); ?></td>
                                <td>
                                    <a
                                        href="<?php echo esc_url($payslipUrls['payslip_' . $payslip->id] ?? '#'); ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <?php TablerIcon::print('eye', 'me-1'); ?>
                                        <?php esc_html_e('View', 'mf-staffcore-lite'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $pageUrl = static function (int $page) use ($pagination, $filterQuery): string {
            return $pagination->pageUrl($page, $filterQuery);
        };
        include MF_STAFFCORE_PATH . 'ui/components/table-pagination.php';
        ?>
    </div>
</div>

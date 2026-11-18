<?php

declare(strict_types=1);

/**
 * @var \MF\StaffCore\Modules\Payroll\DTO\Payroll|null $payroll
 * @var \MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformation|null $company
 * @var \MF\StaffCore\Modules\Employees\DTO\Employee|null $employee
 * @var string|null $currencyCode
 */

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PayrollPresenter;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="staffcore-payslip-page" data-staffcore-module="payroll-payslip" data-role="print-area">
    <?php if ($payroll === null) : ?>
        <div class="staffcore-card" role="alert">
            <div class="staffcore-card-content staffcore-danger">
            <?php esc_html_e('Payroll record not found.', 'mf-staffcore-lite'); ?>
            </div>
        </div>
    <?php else : ?>
        <div class="staffcore-print-bar">
            <button type="button" class="staffcore-print-btn" data-action="print-payslip">
                <span class="staffcore-icon-wrap"><?php TablerIcon::print('printer'); ?></span>
                <?php esc_html_e('Print', 'mf-staffcore-lite'); ?>
            </button>
        </div>

        <div class="staffcore-payslip-card">
            <div class="staffcore-card-body">
                <div class="staffcore-payslip-top mb-4">
                    <div>
                        <?php if ($company?->logo !== null && $company->logo !== '') : ?>
                            <img
                                src="<?php echo esc_url($company->logo); ?>"
                                alt="<?php esc_attr_e('Company logo', 'mf-staffcore-lite'); ?>"
                                class="staffcore-payslip-logo mb-2"
                            >
                        <?php endif; ?>
                        <h3 class="mb-1"><?php echo esc_html($company?->name ?? 'StaffCore'); ?></h3>
                        <?php foreach (PayrollPresenter::companyMetaLines($company) as $line) : ?>
                            <div class="staffcore-muted"><?php echo esc_html($line); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="staffcore-align-end">
                        <div class="staffcore-badge mb-2 staffcore-payslip-title">
                            <?php esc_html_e('Payslip', 'mf-staffcore-lite'); ?>
                        </div>
                        <div class="staffcore-muted">
                            <?php echo wp_kses_post(PayrollPresenter::periodLabel($payroll->payrollMonth, $payroll->payrollYear)); ?>
                        </div>
                        <div class="staffcore-muted">
                            <?php esc_html_e('Generated:', 'mf-staffcore-lite'); ?>
                            <?php echo esc_html(gmdate('d M Y, H:i')); ?>
                        </div>
                        <div class="staffcore-muted">
                            <?php esc_html_e('Payslip ID:', 'mf-staffcore-lite'); ?>
                            <?php echo esc_html('PAY-' . str_pad((string) $payroll->id, 5, '0', STR_PAD_LEFT)); ?>
                        </div>
                    </div>
                </div>

                <div class="staffcore-payslip-grid mb-4">
                    <div class="staffcore-card mb-0">
                        <div class="staffcore-card-header"><h3 class="staffcore-card-title"><?php esc_html_e('Employee Information', 'mf-staffcore-lite'); ?></h3></div>
                        <div class="staffcore-card-content">
                            <dl class="staffcore-payslip-meta">
                                <dt><?php esc_html_e('Employee Name', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html($payroll->employeeName ?? '—'); ?></dd>
                                <dt><?php esc_html_e('Employee Code', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html($payroll->employeeCode ?? '—'); ?></dd>
                                <dt><?php esc_html_e('Department', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html($employee?->departmentName ?? '—'); ?></dd>
                                <dt><?php esc_html_e('Position', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html($employee?->positionTitle ?? '—'); ?></dd>
                            </dl>
                        </div>
                    </div>

                    <div class="staffcore-card mb-0">
                        <div class="staffcore-card-header"><h3 class="staffcore-card-title"><?php esc_html_e('Payment Information', 'mf-staffcore-lite'); ?></h3></div>
                        <div class="staffcore-card-content">
                            <dl class="staffcore-payslip-meta">
                                <dt><?php esc_html_e('Period', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo wp_kses_post(PayrollPresenter::periodLabel($payroll->payrollMonth, $payroll->payrollYear)); ?></dd>
                                <dt><?php esc_html_e('Payment Date', 'mf-staffcore-lite'); ?></dt>
                                <dd>
                                    <?php
                                    $paymentTimestamp = $payroll->paymentDate !== null ? strtotime($payroll->paymentDate) : false;
                                    echo esc_html($paymentTimestamp !== false ? gmdate('d M Y', $paymentTimestamp) : '—');
                                    ?>
                                </dd>
                                <dt><?php esc_html_e('Payment Status', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo wp_kses_post(PayrollPresenter::paymentStatusLabel($payroll->paymentStatus)); ?></dd>
                                <dt><?php esc_html_e('Payment Method', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo wp_kses_post(PayrollPresenter::paymentMethodLabel($payroll->paymentMethod)); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="staffcore-payslip-grid mb-4">
                    <div class="staffcore-card mb-0">
                        <div class="staffcore-card-header"><h3 class="staffcore-card-title"><?php esc_html_e('Earnings', 'mf-staffcore-lite'); ?></h3></div>
                        <div class="staffcore-card-content p-0">
                            <table class="staffcore-table">
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e('Basic Salary', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end staffcore-strong"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->basicSalary, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Allowances', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->allowances, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Overtime Amount', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->overtimeAmount, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Bonus', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->bonus, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Commission', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->commission, $currencyCode)); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="staffcore-card mb-0">
                        <div class="staffcore-card-header"><h3 class="staffcore-card-title"><?php esc_html_e('Deductions', 'mf-staffcore-lite'); ?></h3></div>
                        <div class="staffcore-card-content p-0">
                            <table class="staffcore-table">
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e('Tax', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->tax, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Insurance', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->insurance, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Advance Deduction', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->advanceDeduction, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Other Deductions', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->otherDeductions, $currencyCode)); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="staffcore-danger staffcore-strong"><?php esc_html_e('Total Deductions', 'mf-staffcore-lite'); ?></td>
                                        <td class="staffcore-align-end staffcore-danger staffcore-strong"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->totalDeductions(), $currencyCode)); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="staffcore-payslip-grid mb-3">
                    <div class="staffcore-card mb-0">
                        <div class="staffcore-card-header"><h3 class="staffcore-card-title"><?php esc_html_e('Attendance Summary', 'mf-staffcore-lite'); ?></h3></div>
                        <div class="staffcore-card-content">
                            <dl class="staffcore-payslip-meta">
                                <dt><?php esc_html_e('Working Days', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html((string) $payroll->workingDays); ?></dd>
                                <dt><?php esc_html_e('Present Days', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html((string) $payroll->presentDays); ?></dd>
                                <dt><?php esc_html_e('Absent Days', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html((string) $payroll->absentDays); ?></dd>
                                <dt><?php esc_html_e('Leave Days', 'mf-staffcore-lite'); ?></dt>
                                <dd><?php echo esc_html((string) $payroll->leaveDays); ?></dd>
                            </dl>
                        </div>
                    </div>

                    <div class="staffcore-payslip-net d-flex flex-column justify-content-center align-items-center p-4">
                        <div class="text-uppercase small opacity-75"><?php esc_html_e('Net Salary', 'mf-staffcore-lite'); ?></div>
                        <div class="display-5 fw-bold my-2"><?php echo wp_kses_post(PayrollPresenter::formatMoneyWithCurrency($payroll->netPay(), $currencyCode)); ?></div>
                        <div class="small text-uppercase opacity-75"><?php esc_html_e('Amount Payable', 'mf-staffcore-lite'); ?></div>
                    </div>
                </div>

                <div class="staffcore-payslip-footer pt-3 mt-3">
                    <div class="staffcore-muted">
                        <?php esc_html_e('This document is an official salary payment record.', 'mf-staffcore-lite'); ?>
                    </div>
                    <?php if ($company !== null && $company->website !== null && $company->website !== '') : ?>
                        <div class="staffcore-muted mt-1">
                            <a href="<?php echo esc_url($company->website); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($company->website); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyPayslips;

use MF\StaffCore\Modules\MyPayslips\DTO\EmployeePayslipSummary;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PayrollPresenter;

final class EmployeePayslipStatCards
{
    /**
     * @return list<array{
     *     label: string,
     *     value: int|string,
     *     icon: string,
     *     tone: string,
     *     hint?: string,
     *     hintTone?: string
     * }>
     */
    public static function build(EmployeePayslipSummary $summary, ?string $currencyCode): array
    {
        return [
            [
                'label' => __('Total Payslips', 'mf-staffcore-lite'),
                'value' => $summary->totalPayslips,
                'icon' => 'file-invoice',
                'tone' => 'azure',
                'hint' => __('All Time', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
            [
                'label' => __('This Year Earnings', 'mf-staffcore-lite'),
                'value' => PayrollPresenter::formatMoneyWithCurrency($summary->thisYearEarnings, $currencyCode),
                'icon' => 'coin',
                'tone' => 'success',
                'hint' => __('This Year', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
            [
                'label' => __('Total Earnings', 'mf-staffcore-lite'),
                'value' => PayrollPresenter::formatMoneyWithCurrency($summary->totalPaidEarnings, $currencyCode),
                'icon' => 'wallet',
                'tone' => 'azure',
                'hint' => __('Paid Only', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Payroll\DTO;

final class PayrollInput
{
    public function __construct(
        public readonly int $employeeId,
        public readonly int $payrollMonth,
        public readonly int $payrollYear,
        public readonly float $basicSalary,
        public readonly float $allowances,
        public readonly float $overtimeAmount,
        public readonly float $bonus,
        public readonly float $commission,
        public readonly float $tax,
        public readonly float $insurance,
        public readonly float $advanceDeduction,
        public readonly float $otherDeductions,
        public readonly int $workingDays,
        public readonly int $presentDays,
        public readonly int $absentDays,
        public readonly int $leaveDays,
        public readonly float $overtimeHours,
        public readonly string $paymentStatus,
        public readonly ?string $paymentDate,
        public readonly ?string $paymentMethod,
        public readonly ?string $paymentReference,
        public readonly ?string $notes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'payroll_month' => $this->payrollMonth,
            'payroll_year' => $this->payrollYear,
            'basic_salary' => number_format($this->basicSalary, 2, '.', ''),
            'allowances' => number_format($this->allowances, 2, '.', ''),
            'overtime_amount' => number_format($this->overtimeAmount, 2, '.', ''),
            'bonus' => number_format($this->bonus, 2, '.', ''),
            'commission' => number_format($this->commission, 2, '.', ''),
            'tax' => number_format($this->tax, 2, '.', ''),
            'insurance' => number_format($this->insurance, 2, '.', ''),
            'advance_deduction' => number_format($this->advanceDeduction, 2, '.', ''),
            'other_deductions' => number_format($this->otherDeductions, 2, '.', ''),
            'working_days' => $this->workingDays,
            'present_days' => $this->presentDays,
            'absent_days' => $this->absentDays,
            'leave_days' => $this->leaveDays,
            'overtime_hours' => number_format($this->overtimeHours, 2, '.', ''),
            'payment_status' => $this->paymentStatus,
            'payment_date' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'notes' => $this->notes,
        ];
    }
}

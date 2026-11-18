<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Payroll\DTO;

final class Payroll
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_ON_HOLD = 'ON_HOLD';

    public const METHOD_CASH = 'CASH';
    public const METHOD_BANK = 'BANK';
    public const METHOD_CHEQUE = 'CHEQUE';

    public function __construct(
        public readonly int $id,
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
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $employeeName = null,
        public readonly ?string $employeeCode = null,
        public readonly ?string $employeePhoto = null,
    ) {
    }

    public function grossPay(): float
    {
        return $this->basicSalary
            + $this->allowances
            + $this->overtimeAmount
            + $this->bonus
            + $this->commission;
    }

    public function totalDeductions(): float
    {
        return $this->tax
            + $this->insurance
            + $this->advanceDeduction
            + $this->otherDeductions;
    }

    public function netPay(): float
    {
        return max(0, $this->grossPay() - $this->totalDeductions());
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            employeeId: (int) ($row['employee_id'] ?? 0),
            payrollMonth: (int) ($row['payroll_month'] ?? 0),
            payrollYear: (int) ($row['payroll_year'] ?? 0),
            basicSalary: self::toFloat($row['basic_salary'] ?? 0),
            allowances: self::toFloat($row['allowances'] ?? 0),
            overtimeAmount: self::toFloat($row['overtime_amount'] ?? 0),
            bonus: self::toFloat($row['bonus'] ?? 0),
            commission: self::toFloat($row['commission'] ?? 0),
            tax: self::toFloat($row['tax'] ?? 0),
            insurance: self::toFloat($row['insurance'] ?? 0),
            advanceDeduction: self::toFloat($row['advance_deduction'] ?? 0),
            otherDeductions: self::toFloat($row['other_deductions'] ?? 0),
            workingDays: (int) ($row['working_days'] ?? 0),
            presentDays: (int) ($row['present_days'] ?? 0),
            absentDays: (int) ($row['absent_days'] ?? 0),
            leaveDays: (int) ($row['leave_days'] ?? 0),
            overtimeHours: self::toFloat($row['overtime_hours'] ?? 0),
            paymentStatus: (string) ($row['payment_status'] ?? self::STATUS_PENDING),
            paymentDate: self::nullableString($row['payment_date'] ?? null),
            paymentMethod: self::nullableString($row['payment_method'] ?? null),
            paymentReference: self::nullableString($row['payment_reference'] ?? null),
            notes: self::nullableString($row['notes'] ?? null),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            employeeName: self::nullableString($row['employee_name'] ?? null),
            employeeCode: self::nullableString($row['employee_code'] ?? null),
            employeePhoto: self::nullableString($row['employee_photo'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employeeId,
            'payroll_month' => $this->payrollMonth,
            'payroll_year' => $this->payrollYear,
            'basic_salary' => $this->basicSalary,
            'allowances' => $this->allowances,
            'overtime_amount' => $this->overtimeAmount,
            'bonus' => $this->bonus,
            'commission' => $this->commission,
            'tax' => $this->tax,
            'insurance' => $this->insurance,
            'advance_deduction' => $this->advanceDeduction,
            'other_deductions' => $this->otherDeductions,
            'working_days' => $this->workingDays,
            'present_days' => $this->presentDays,
            'absent_days' => $this->absentDays,
            'leave_days' => $this->leaveDays,
            'overtime_hours' => $this->overtimeHours,
            'payment_status' => $this->paymentStatus,
            'payment_date' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'notes' => $this->notes,
            'gross_pay' => $this->grossPay(),
            'total_deductions' => $this->totalDeductions(),
            'net_pay' => $this->netPay(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'employee_name' => $this->employeeName,
            'employee_code' => $this->employeeCode,
            'employee_photo' => $this->employeePhoto,
        ];
    }

    private static function toFloat(mixed $value): float
    {
        return (float) $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

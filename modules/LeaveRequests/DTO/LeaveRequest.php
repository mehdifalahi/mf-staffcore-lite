<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\LeaveRequests\DTO;

final class LeaveRequest
{
    public const TYPE_ANNUAL = 'Annual';
    public const TYPE_SICK = 'Sick';
    public const TYPE_UNPAID = 'Unpaid';
    public const TYPE_MATERNITY = 'Maternity';
    public const TYPE_PATERNITY = 'Paternity';
    public const TYPE_OTHER = 'Other';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public function __construct(
        public readonly int $id,
        public readonly int $employeeId,
        public readonly string $leaveType,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly int $totalDays,
        public readonly ?string $reason,
        public readonly ?string $notes,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
        public readonly ?string $employeeName = null,
        public readonly ?string $employeeCode = null,
        public readonly ?string $employeePhoto = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function leaveTypes(): array
    {
        return [
            self::TYPE_ANNUAL,
            self::TYPE_SICK,
            self::TYPE_UNPAID,
            self::TYPE_MATERNITY,
            self::TYPE_PATERNITY,
            self::TYPE_OTHER,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            employeeId: (int) ($row['employee_id'] ?? 0),
            leaveType: (string) ($row['leave_type'] ?? ''),
            startDate: (string) ($row['start_date'] ?? ''),
            endDate: (string) ($row['end_date'] ?? ''),
            totalDays: (int) ($row['total_days'] ?? 0),
            reason: self::nullableString($row['reason'] ?? null),
            notes: self::nullableString($row['notes'] ?? null),
            status: (string) ($row['status'] ?? self::STATUS_PENDING),
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
            'leave_type' => $this->leaveType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_days' => $this->totalDays,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'employee_name' => $this->employeeName,
            'employee_code' => $this->employeeCode,
            'employee_photo' => $this->employeePhoto,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

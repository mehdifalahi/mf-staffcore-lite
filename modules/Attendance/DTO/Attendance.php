<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Attendance\DTO;

final class Attendance
{
    public const STATUS_PRESENT = 'PRESENT';
    public const STATUS_LATE = 'LATE';
    public const STATUS_ABSENT = 'ABSENT';
    public const STATUS_ON_LEAVE = 'ON_LEAVE';

    public const TYPE_MANUAL = 'MANUAL';
    public const TYPE_AUTO = 'AUTO';

    public function __construct(
        public readonly int $id,
        public readonly int $employeeId,
        public readonly string $attendanceDate,
        public readonly ?string $clockIn,
        public readonly ?string $clockOut,
        public readonly string $status,
        public readonly string $attendanceType,
        public readonly ?string $remarks,
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
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            employeeId: (int) ($row['employee_id'] ?? 0),
            attendanceDate: (string) ($row['attendance_date'] ?? ''),
            clockIn: self::nullableString($row['clock_in'] ?? null),
            clockOut: self::nullableString($row['clock_out'] ?? null),
            status: (string) ($row['status'] ?? self::STATUS_PRESENT),
            attendanceType: (string) ($row['attendance_type'] ?? self::TYPE_MANUAL),
            remarks: self::nullableString($row['remarks'] ?? null),
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
            'attendance_date' => $this->attendanceDate,
            'clock_in' => $this->clockIn,
            'clock_out' => $this->clockOut,
            'status' => $this->status,
            'attendance_type' => $this->attendanceType,
            'remarks' => $this->remarks,
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

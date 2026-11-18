<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Attendance\DTO;

final class AttendanceInput
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $attendanceDate,
        public readonly ?string $clockIn,
        public readonly ?string $clockOut,
        public readonly string $status,
        public readonly string $attendanceType,
        public readonly ?string $remarks,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'attendance_date' => $this->attendanceDate,
            'clock_in' => $this->clockIn,
            'clock_out' => $this->clockOut,
            'status' => $this->status,
            'attendance_type' => $this->attendanceType,
            'remarks' => $this->remarks,
        ];
    }
}

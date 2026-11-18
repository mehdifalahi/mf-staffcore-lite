<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\LeaveRequests\DTO;

final class LeaveRequestInput
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $leaveType,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly int $totalDays,
        public readonly ?string $reason,
        public readonly ?string $notes,
        public readonly string $status,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'leave_type' => $this->leaveType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_days' => $this->totalDays,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}

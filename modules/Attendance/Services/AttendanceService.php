<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Attendance\Services;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\EmployeeScopeResolver;
use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Attendance\DTO\AttendanceInput;
use MF\StaffCore\Modules\Attendance\DTO\AttendanceListFilter;
use MF\StaffCore\Modules\Attendance\DTO\EmployeeAttendanceMonthSummary;
use MF\StaffCore\Modules\Attendance\Repositories\AttendanceRepository;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class AttendanceService
{
    private const REMARKS_MAX = 65535;

    public function __construct(
        private readonly AttendanceRepository $repository,
        private readonly EmployeeScopeResolver $employeeScope,
    ) {
    }
/**
     * @param list<int> $ids
     */
    public function bulkDelete(array $ids, int $actorUserId, ?array $scopeEmployeeIds = null): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute(
            $ids,
            fn (int $id) => $this->delete($id, $scopeEmployeeIds),
        );
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    private function applyScope(AttendanceInput $input, ?array $scopeEmployeeIds): AttendanceInput
    {
        if ($scopeEmployeeIds === null) {
            return $input;
        }

        try {
            $employeeId = $this->employeeScope->normalizeInputEmployeeId($input->employeeId, $scopeEmployeeIds);
        } catch (AuthorizationException $exception) {
            throw new ValidationException( esc_html( $exception->getMessage() ) );
        }

        return new AttendanceInput(
            employeeId: $employeeId,
            attendanceDate: $input->attendanceDate,
            clockIn: $input->clockIn,
            clockOut: $input->clockOut,
            status: $input->status,
            attendanceType: $input->attendanceType,
            remarks: $input->remarks,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): AttendanceInput
    {
        $employeeId = isset($raw['employee_id']) && $raw['employee_id'] !== ''
            ? (int) $raw['employee_id']
            : 0;

        return new AttendanceInput(
            employeeId: $employeeId,
            attendanceDate: (string) ($raw['attendance_date'] ?? ''),
            clockIn: isset($raw['clock_in']) ? (string) $raw['clock_in'] : null,
            clockOut: isset($raw['clock_out']) ? (string) $raw['clock_out'] : null,
            status: (string) ($raw['status'] ?? Attendance::STATUS_PRESENT),
            attendanceType: (string) ($raw['attendance_type'] ?? Attendance::TYPE_MANUAL),
            remarks: isset($raw['remarks']) ? (string) $raw['remarks'] : null,
        );
    }

    private function normalize(AttendanceInput $input): AttendanceInput
    {
        return new AttendanceInput(
            employeeId: $input->employeeId,
            attendanceDate: trim($input->attendanceDate),
            clockIn: $this->normalizeTime($input->clockIn),
            clockOut: $this->normalizeTime($input->clockOut),
            status: strtoupper(trim($input->status)),
            attendanceType: strtoupper(trim($input->attendanceType)),
            remarks: $this->trimNullable($input->remarks),
        );
    }

    private function normalizeTime(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $trimmed) === 1) {
            return $trimmed . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $trimmed) === 1) {
            return $trimmed;
        }

        return $trimmed;
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assert(AttendanceInput $input): void
    {
        if ($input->employeeId <= 0) {
            throw new ValidationException(esc_html('Employee is required.'));
        }

        if (! $this->repository->employeeExists($input->employeeId)) {
            throw new ValidationException(esc_html('Selected employee does not exist.'));
        }

        if ($input->attendanceDate === '') {
            throw new ValidationException(esc_html('Attendance date is required.'));
        }

        if (! $this->isValidDate($input->attendanceDate)) {
            throw new ValidationException(esc_html('Invalid attendance date.'));
        }

        $statuses = [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_LATE,
            Attendance::STATUS_ABSENT,
            Attendance::STATUS_ON_LEAVE,
        ];

        if (! in_array($input->status, $statuses, true)) {
            throw new ValidationException(esc_html('Invalid attendance status.'));
        }

        $types = [Attendance::TYPE_MANUAL, Attendance::TYPE_AUTO];

        if (! in_array($input->attendanceType, $types, true)) {
            throw new ValidationException(esc_html('Invalid attendance type.'));
        }

        if ($input->remarks !== null && strlen($input->remarks) > self::REMARKS_MAX) {
            throw new ValidationException(esc_html('Remarks are too long.'));
        }

        if ($input->clockIn !== null && ! $this->isValidTime($input->clockIn)) {
            throw new ValidationException(esc_html('Invalid clock-in time.'));
        }

        if ($input->clockOut !== null && ! $this->isValidTime($input->clockOut)) {
            throw new ValidationException(esc_html('Invalid clock-out time.'));
        }

        if ($input->clockIn !== null && $input->clockOut !== null) {
            if (strtotime($input->clockIn) >= strtotime($input->clockOut)) {
                throw new ValidationException(esc_html('Clock-out must be after clock-in.'));
            }
        }
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function isValidTime(string $time): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('H:i:s', $time);

        if ($parsed !== false && $parsed->format('H:i:s') === $time) {
            return true;
        }

        $parsed = \DateTimeImmutable::createFromFormat('H:i', $time);

        return $parsed !== false && $parsed->format('H:i') === substr($time, 0, 5);
    }

    private function timeIsEarlier(string $a, string $b): bool
    {
        return strtotime($a) < strtotime($b);
    }

    private function timeIsLater(string $a, string $b): bool
    {
        return strtotime($a) > strtotime($b);
    }
}


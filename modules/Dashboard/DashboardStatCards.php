<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard;

use MF\StaffCore\Authorization\AuthorizationService;
use MF\StaffCore\Modules\Dashboard\DTO\DashboardOverviewStats;

/**
 * Builds dashboard summary card metadata for the admin UI.
 */
final class DashboardStatCards
{
    /**
     * @param callable(string): string $label
     * @return list<array{
     *     label: string,
     *     value: int|string,
     *     icon: string,
     *     tone: string,
     *     hint?: string,
     *     hintTone?: string
     * }>
     */
    public static function build(
        DashboardOverviewStats $stats,
        AuthorizationService $auth,
        callable $label,
    ): array {
        $cards = [];

        if ($auth->can('employees.view')) {
            $cards[] = [
                'label' => $label('Total Employees'),
                'value' => $stats->totalEmployees,
                'icon' => 'users',
                'tone' => 'primary',
                'hint' => sprintf('↑ %s%% Active', number_format($stats->activeEmployeePercent(), 1)),
                'hintTone' => 'success',
            ];
            $cards[] = [
                'label' => $label('Active Employees'),
                'value' => $stats->activeEmployees,
                'icon' => 'user-check',
                'tone' => 'success',
                'hint' => $label('Working Now'),
                'hintTone' => 'success',
            ];
        }

        if ($auth->can('attendance.view')) {
            $cards[] = [
                'label' => $label('Today Attendance'),
                'value' => $stats->attendanceToday,
                'icon' => 'clock',
                'tone' => 'azure',
                'hint' => sprintf('%s%% Present', number_format($stats->attendancePresentPercent(), 1)),
                'hintTone' => 'azure',
            ];
        }

        if ($auth->can('leave_requests.view')) {
            $cards[] = [
                'label' => $label('On Leave Today'),
                'value' => $stats->onLeaveToday,
                'icon' => 'calendar-off',
                'tone' => 'warning',
                'hint' => $label('Approved'),
                'hintTone' => 'warning',
            ];
        }

        if ($auth->can('departments.view')) {
            $cards[] = [
                'label' => $label('Departments'),
                'value' => $stats->departments,
                'icon' => 'building',
                'tone' => 'primary',
            ];
        }

        if ($auth->can('leave_requests.view')) {
            $cards[] = [
                'label' => $label('Pending Leaves'),
                'value' => $stats->pendingLeaves,
                'icon' => 'hourglass',
                'tone' => 'warning',
            ];
        }

        if ($auth->can('assets.view')) {
            $cards[] = [
                'label' => $label('Total Assets'),
                'value' => $stats->totalAssets,
                'icon' => 'device-laptop',
                'tone' => 'azure',
            ];
        }

        if ($auth->can('payroll.view')) {
            $cards[] = [
                'label' => $label('Payroll Records'),
                'value' => $stats->payrollRecords,
                'icon' => 'cash',
                'tone' => 'success',
            ];
        }

        return $cards;
    }
}

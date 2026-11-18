<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Dashboard;

use MF\StaffCore\Authorization\AuthorizationService;
use MF\StaffCore\Modules\Dashboard\DTO\EmployeeDashboardStats;

/**
 * Stat cards for the employee-role dashboard layout.
 */
final class EmployeeDashboardStatCards
{
    /**
     * @param callable(string): string $label
     * @param array<string, string> $urls
     * @return list<array{
     *     label: string,
     *     value: int|string,
     *     icon: string,
     *     tone: string,
     *     hint?: string,
     *     hintTone?: string,
     *     url?: string
     * }>
     */
    public static function build(
        EmployeeDashboardStats $stats,
        AuthorizationService $auth,
        callable $label,
        array $urls = [],
    ): array {
        $cards = [];

        if ($auth->can('leave_requests.view')) {
            $leaveUrl = $urls['leave_requests'] ?? null;
            $cards[] = [
                'label' => $label('Leave Balance'),
                'value' => $stats->leaveBalanceDays,
                'icon' => 'calendar',
                'tone' => 'success',
                'hint' => $label('Days Remaining'),
                'hintTone' => 'success',
                'url' => $leaveUrl,
            ];
            $cards[] = [
                'label' => $label('Pending Requests'),
                'value' => $stats->pendingLeaveRequests,
                'icon' => 'hourglass',
                'tone' => 'warning',
                'hint' => $label('Awaiting Approval'),
                'hintTone' => 'warning',
                'url' => $leaveUrl,
            ];
        }

        if ($auth->can('attendance.view')) {
            $cards[] = [
                'label' => $label('Attendance'),
                'value' => $stats->attendanceDaysThisMonth,
                'icon' => 'clock',
                'tone' => 'azure',
                'hint' => $label('Days This Month'),
                'hintTone' => 'azure',
                'url' => $urls['attendance'] ?? null,
            ];
        }

        if ($auth->can('documents.view')) {
            $cards[] = [
                'label' => $label('My Documents'),
                'value' => $stats->issuedDocumentCount,
                'icon' => 'file-text',
                'tone' => 'cyan',
                'hint' => $label('Available'),
                'hintTone' => 'cyan',
                'url' => $urls['documents'] ?? null,
            ];
        }

        return $cards;
    }
}

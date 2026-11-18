<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Attendance;

use MF\StaffCore\Modules\Attendance\DTO\EmployeeAttendanceMonthSummary;

final class EmployeeAttendanceStatCards
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
    public static function build(EmployeeAttendanceMonthSummary $summary): array
    {
        return [
            [
                'label' => __('Total Days', 'mf-staffcore-lite'),
                'value' => $summary->totalDays,
                'icon' => 'calendar',
                'tone' => 'azure',
                'hint' => __('This Month', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
            [
                'label' => __('Present', 'mf-staffcore-lite'),
                'value' => $summary->presentDays,
                'icon' => 'circle-check',
                'tone' => 'success',
                'hint' => __('Days', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
            [
                'label' => __('Late', 'mf-staffcore-lite'),
                'value' => $summary->lateDays,
                'icon' => 'clock',
                'tone' => 'warning',
                'hint' => __('Days', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
            [
                'label' => __('Total Hours', 'mf-staffcore-lite'),
                'value' => number_format($summary->totalHours, 1),
                'icon' => 'hourglass',
                'tone' => 'cyan',
                'hint' => __('This Month', 'mf-staffcore-lite'),
                'hintTone' => 'secondary',
            ],
        ];
    }
}

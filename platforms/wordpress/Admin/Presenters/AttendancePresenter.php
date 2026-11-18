<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Modules\Attendance\DTO\Attendance;
use MF\StaffCore\Modules\Attendance\DTO\AttendanceListFilter;
use MF\StaffCore\Modules\Attendance\EmployeeAttendanceStatCards;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\AttendanceAjaxHandler;

final class AttendancePresenter
{
public const PUNCH_PAGE_PARAM = 'sc_punch_paged';

    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->attendance();
        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $scopeEmployeeIds = null;
        $scopeError = null;

        try {
            $scopeEmployeeIds = $container->scopeEmployeeIdsForModule('attendance');
        } catch (AuthorizationException $exception) {
            $scopeError = $exception->getMessage();
        }

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $isEmployeeScoped = $container->employeeScopeResolver()->isSingleEmployeeScope($scopeEmployeeIds);

        if ($scopeError === null && $isEmployeeScoped && $scopeEmployeeIds !== null && count($scopeEmployeeIds) === 1) {
            $this->renderEmployeePage(
                $container,
                $service,
                $pagination,
                $scopeEmployeeIds[0],
                $page,
                $perPage,
            );

            return;
        }

        $listFilter = self::resolveListFilter($scopeEmployeeIds);
        $filterQuery = $listFilter->toQueryArgs();

        if ($scopeError === null) {
            $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);

            $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
            if ($page !== $result->page) {
                $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);
            }

            $attendanceRecords = $result->items;
            $currentPage = $result->page;
            $totalPages = $result->totalPages();
            $totalItems = $result->total;
        } else {
            $attendanceRecords = [];
            $currentPage = 1;
            $totalPages = 1;
            $totalItems = 0;
        }

        $perPage = $perPage;
        $employees = $scopeError === null ? $service->employeeOptions($scopeEmployeeIds) : [];
        $filterRoute = new AdminRoute('hr-management', 'attendance');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = AttendanceAjaxHandler::nonce();
        $scopeError = $scopeError ?? null;
        $perms = $container->authorization()->modulePermissions('attendance');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $isEmployeeScoped = $container->employeeScopeResolver()->isSingleEmployeeScope($scopeEmployeeIds);
        $bulkDeletePermission = 'attendance.delete';
        $bulkDeleteModalId = 'staffcore-attendance-bulk-delete-modal';
        $bulkEntityPlural = __('attendance records', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_attendance_bulk_delete';
        $listPageQuery = $filterQuery;

        include MF_STAFFCORE_PATH . 'modules/Attendance/Views/page.php';
    }

    private function renderEmployeePage(
        \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer $container,
        \MF\StaffCore\Modules\Attendance\Services\AttendanceService $service,
        ListTablePagination $pagination,
        int $employeeId,
        int $page,
        int $perPage,
    ): void {
        $urlBuilder = new AdminUrlBuilder();
        $monthContext = self::resolveEmployeeMonthContext();
        $listFilter = $monthContext['filter'];
        $filterQuery = $monthContext['query'];
        $filterRoute = new AdminRoute('hr-management', 'attendance');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $scopeEmployeeIds = [$employeeId];

        $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);

        $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);
        }

        $attendanceRecords = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $monthSummary = $service->employeeMonthSummary(
            $employeeId,
            $monthContext['year'],
            $monthContext['month'],
        );
        $statCards = EmployeeAttendanceStatCards::build($monthSummary);
        $filterYear = $monthContext['year'];
        $filterMonth = $monthContext['month'];
        $periodLabel = $monthContext['periodLabel'];
        $scopeError = null;
include MF_STAFFCORE_PATH . 'modules/Attendance/Views/employee-page.php';
    }
/**
     * @return array{
     *     year: int,
     *     month: int,
     *     filter: AttendanceListFilter,
     *     query: array<string, scalar>,
     *     periodLabel: string
     * }
     */
    public static function resolveEmployeeMonthContext(): array
    {
        $year = isset($_GET[AttendanceListFilter::QUERY_YEAR])
            ? (int) $_GET[AttendanceListFilter::QUERY_YEAR]
            : (int) gmdate('Y');
        $month = isset($_GET[AttendanceListFilter::QUERY_MONTH])
            ? (int) $_GET[AttendanceListFilter::QUERY_MONTH]
            : (int) gmdate('n');
        $month = max(1, min(12, $month));
        $year = max(2000, min(2100, $year));
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = (int) gmdate('t', strtotime($dateFrom . ' UTC') ?: time());
        $dateTo = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
        $timestamp = strtotime($dateFrom . ' UTC') ?: time();

        return [
            'year' => $year,
            'month' => $month,
            'filter' => new AttendanceListFilter(
                dateFrom: $dateFrom,
                dateTo: $dateTo,
            ),
            'query' => [
                AttendanceListFilter::QUERY_YEAR => $year,
                AttendanceListFilter::QUERY_MONTH => $month,
            ],
            'periodLabel' => (string) wp_date('F Y', $timestamp),
        ];
    }

    public static function monthFilterOptions(int $selectedMonth): void
    {
        for ($month = 1; $month <= 12; ++$month) {
            $timestamp = gmmktime(0, 0, 0, $month, 1, 2000);
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) $month),
                selected($selectedMonth, $month, false),
                esc_html((string) wp_date('F', $timestamp)),
            );
        }
    }

    public static function yearFilterOptions(int $selectedYear): void
    {
        $currentYear = (int) gmdate('Y');

        for ($year = $currentYear - 5; $year <= $currentYear + 1; ++$year) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) $year),
                selected($selectedYear, $year, false),
                esc_html((string) $year),
            );
        }
    }

    /**
     * @param array<string, scalar> $preserveQuery
     */
    public static function resolveListFilter(?array $scopeEmployeeIds = null): AttendanceListFilter
    {
        $employeeId = isset($_GET[AttendanceListFilter::QUERY_EMPLOYEE])
            ? (int) $_GET[AttendanceListFilter::QUERY_EMPLOYEE]
            : 0;
        $status = isset($_GET[AttendanceListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[AttendanceListFilter::QUERY_STATUS]))
            : '';
        $dateFrom = isset($_GET[AttendanceListFilter::QUERY_DATE_FROM])
            ? sanitize_text_field(wp_unslash((string) $_GET[AttendanceListFilter::QUERY_DATE_FROM]))
            : '';
        $dateTo = isset($_GET[AttendanceListFilter::QUERY_DATE_TO])
            ? sanitize_text_field(wp_unslash((string) $_GET[AttendanceListFilter::QUERY_DATE_TO]))
            : '';

        if ($scopeEmployeeIds !== null && count($scopeEmployeeIds) === 1) {
            $employeeId = $scopeEmployeeIds[0];
        }

        return new AttendanceListFilter(
            employeeId: $employeeId > 0 ? $employeeId : null,
            status: $status !== '' && $status !== 'all' ? strtoupper($status) : null,
            dateFrom: $dateFrom !== '' ? $dateFrom : null,
            dateTo: $dateTo !== '' ? $dateTo : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function displayTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '—';
        }

        if (preg_match('/^(\d{2}):(\d{2})/', $time, $matches) === 1) {
            return esc_html($matches[1] . ':' . $matches[2]);
        }

        return esc_html($time);
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_PRESENT => '<span class="badge bg-success-lt">'
                . esc_html__('Present', 'mf-staffcore-lite') . '</span>',
            Attendance::STATUS_LATE => '<span class="badge bg-warning-lt">'
                . esc_html__('Late', 'mf-staffcore-lite') . '</span>',
            Attendance::STATUS_ABSENT => '<span class="badge bg-danger-lt">'
                . esc_html__('Absent', 'mf-staffcore-lite') . '</span>',
            Attendance::STATUS_ON_LEAVE => '<span class="badge bg-azure-lt">'
                . esc_html__('On leave', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($status) . '</span>',
        };
    }

    public static function typeBadge(string $type): string
    {
        if ($type === Attendance::TYPE_AUTO) {
            return '<span class="badge bg-purple-lt">' . esc_html__('Auto', 'mf-staffcore-lite') . '</span>';
        }

        return '<span class="badge bg-secondary-lt">' . esc_html__('Manual', 'mf-staffcore-lite') . '</span>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function attendancePayload(Attendance $attendance): array
    {
        $data = $attendance->toArray();
        $data['clock_in'] = self::timeForInput($attendance->clockIn);
        $data['clock_out'] = self::timeForInput($attendance->clockOut);

        return $data;
    }

    public static function timeForInput(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        if (preg_match('/^(\d{2}):(\d{2})/', $time, $matches) === 1) {
            return $matches[1] . ':' . $matches[2];
        }

        return $time;
    }

    public static function statusOptions(): void
    {
        $options = [
            Attendance::STATUS_PRESENT => __('Present', 'mf-staffcore-lite'),
            Attendance::STATUS_LATE => __('Late', 'mf-staffcore-lite'),
            Attendance::STATUS_ABSENT => __('Absent', 'mf-staffcore-lite'),
            Attendance::STATUS_ON_LEAVE => __('On leave', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label),
            );
        }
    }

    public static function statusFilterOptions(?string $selected): void
    {
        $options = [
            'all' => __('All Statuses', 'mf-staffcore-lite'),
            Attendance::STATUS_PRESENT => __('Present', 'mf-staffcore-lite'),
            Attendance::STATUS_LATE => __('Late', 'mf-staffcore-lite'),
            Attendance::STATUS_ABSENT => __('Absent', 'mf-staffcore-lite'),
            Attendance::STATUS_ON_LEAVE => __('On leave', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($selected ?? 'all', $value, false),
                esc_html($label),
            );
        }
    }

    /**
     * @param list<array{id: int, label: string}> $employees
     */
    public static function employeeOptions(array $employees, ?int $selectedId = null): void
    {
        foreach ($employees as $employee) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $employee['id'],
                selected($selectedId, (int) $employee['id'], false),
                esc_html($employee['label']),
            );
        }
    }
}

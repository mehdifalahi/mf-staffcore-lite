<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter;
use MF\StaffCore\Modules\Positions\DTO\Position;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\EmployeeAjaxHandler;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\UI\PersonAvatar;

final class EmployeesPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->employees();
        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $scopeEmployeeIds = null;
        $scopeError = null;

        try {
            $scopeEmployeeIds = $container->scopeEmployeeIdsForModule('employees');
        } catch (AuthorizationException $exception) {
            $scopeError = $exception->getMessage();
        }

        $listFilter = self::resolveListFilter();
        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $filterQuery = $listFilter->toQueryArgs();

        if ($scopeError === null) {
            $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);

            $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
            if ($page !== $result->page) {
                $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);
            }

            $employees = $result->items;
            $currentPage = $result->page;
            $totalPages = $result->totalPages();
            $totalItems = $result->total;
        } else {
            $employees = [];
            $currentPage = 1;
            $totalPages = 1;
            $totalItems = 0;
        }
        $departments = $container->departments()->listActive();
        $positions = $container->positions()->listActive();
        $positionsLookup = self::positionsLookupPayload($positions);
        $userAccounts = self::loadUserAccountOptions();
        $filterRoute = new AdminRoute('hr-management', 'employees');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = EmployeeAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('employees');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $showRowViewActions = true;
        $bulkDeletePermission = 'employees.delete';
        $bulkDeleteModalId = 'staffcore-employees-bulk-delete-modal';
        $bulkEntityPlural = __('employees', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_employee_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/Employees/Views/page.php';
    }

    public static function resolveListFilter(): EmployeeListFilter
    {
        $departmentId = isset($_GET[EmployeeListFilter::QUERY_DEPARTMENT])
            ? (int) $_GET[EmployeeListFilter::QUERY_DEPARTMENT]
            : 0;

        $status = isset($_GET[EmployeeListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[EmployeeListFilter::QUERY_STATUS]))
            : '';

        $search = isset($_GET[EmployeeListFilter::QUERY_SEARCH])
            ? sanitize_text_field(wp_unslash((string) $_GET[EmployeeListFilter::QUERY_SEARCH]))
            : '';

        $search = trim($search);

        return new EmployeeListFilter(
            departmentId: $departmentId > 0 ? $departmentId : null,
            employmentStatus: $status !== '' && $status !== 'all'
                ? strtoupper($status)
                : null,
            search: $search !== '' ? $search : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function formatHireDate(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(gmdate('Y-m-d', $timestamp));
    }

    public static function leaveDaysBadge(int $days, string $tone): string
    {
        $class = match ($tone) {
            'annual' => 'bg-blue-lt',
            'remaining' => 'bg-success-lt',
            default => 'bg-secondary-lt',
        };

        return sprintf(
            '<span class="badge %s">%s</span>',
            esc_attr($class),
            esc_html(sprintf(
                /* translators: %d: number of days */
                _n('%d day', '%d days', $days, 'mf-staffcore-lite'),
                $days,
            )),
        );
    }

    public static function departmentBadge(?string $name): string
    {
        if ($name === null || $name === '') {
            return '—';
        }

        return '<span class="badge staffcore-department-badge">' . esc_html($name) . '</span>';
    }

    public static function nameCellHtml(Employee $employee): string
    {
        return PersonAvatar::cellHtml(
            $employee->fullName(),
            $employee->photo,
            $employee->email,
        );
    }

    public static function positionCellHtml(Employee $employee): string
    {
        $title = $employee->positionTitle ?? '—';
        $levelLabel = Employee::positionLevelLabel($employee->positionLevel);

        $html = '<div class="fw-medium">' . esc_html($title) . '</div>';

        if ($levelLabel !== '') {
            $html .= '<div class="text-secondary small">(' . esc_html($levelLabel) . ')</div>';
        }

        return $html;
    }

    public static function statusBadge(string $status): string
    {
        $label = Employee::statusLabel($status);

        $class = match ($status) {
            Employee::STATUS_ACTIVE => 'bg-success-lt',
            Employee::STATUS_ON_LEAVE => 'bg-warning-lt',
            Employee::STATUS_SUSPENDED => 'bg-orange-lt',
            Employee::STATUS_RESIGNED => 'bg-secondary-lt',
            Employee::STATUS_TERMINATED => 'bg-danger-lt',
            default => 'bg-secondary-lt',
        };

        return '<span class="badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function employeePayload(Employee $employee): array
    {
        return array_merge($employee->toArray(), self::employeeViewExtras($employee));
    }

    /**
     * @return array<string, mixed>
     */
    public static function employeeViewExtras(Employee $employee): array
    {
        $totalAllowances = 0.0;

        foreach ($employee->allowances as $allowance) {
            $totalAllowances += $allowance->amount;
        }

        return [
            'system_user_label' => self::resolveSystemUserLabel($employee->userId),
            'position_display' => self::positionDisplay($employee),
            'total_allowances' => $totalAllowances,
            'hire_date_display' => self::formatViewDate($employee->hireDate),
            'birth_date_display' => self::formatViewDate($employee->birthDate),
            'annual_leave_display' => self::formatLeaveDays($employee->annualLeave),
            'remaining_leave_display' => self::formatLeaveDays($employee->remainingLeave),
        ];
    }

    public static function formatViewDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return '—';
        }

        return gmdate('d/m/Y', $timestamp);
    }

    public static function formatLeaveDays(int $days): string
    {
        return sprintf(
            /* translators: %d: number of days */
            _n('%d day', '%d days', $days, 'mf-staffcore-lite'),
            $days,
        );
    }

    public static function positionDisplay(Employee $employee): string
    {
        $title = $employee->positionTitle ?? '';
        $levelLabel = Employee::positionLevelLabel($employee->positionLevel);

        if ($title === '') {
            return '—';
        }

        if ($levelLabel === '') {
            return $title;
        }

        return $title . ' (' . $levelLabel . ')';
    }

    public static function resolveSystemUserLabel(?int $userId): string
    {
        if ($userId === null || $userId <= 0) {
            return '—';
        }

        $label = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance()
            ->employees()
            ->resolvePlatformUserAccountLabel($userId);

        return $label ?? '—';
    }

    /**
     * @param list<Position> $positions
     * @return list<array{id: int, title: string, department_id: int}>
     */
    public static function positionsLookupPayload(array $positions): array
    {
        $payload = [];

        foreach ($positions as $position) {
            $payload[] = [
                'id' => $position->id,
                'title' => $position->title,
                'department_id' => $position->departmentId,
            ];
        }

        return $payload;
    }

    /**
     * @param list<Department> $departments
     */
    public static function departmentOptions(array $departments, ?int $selectedId = null): void
    {
        foreach ($departments as $department) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $department->id,
                selected($selectedId, $department->id, false),
                esc_html($department->name),
            );
        }
    }

    public static function statusFilterOptions(?string $selected): void
    {
        $options = [
            'all' => __('All Statuses', 'mf-staffcore-lite'),
            Employee::STATUS_ACTIVE => Employee::statusLabel(Employee::STATUS_ACTIVE),
            Employee::STATUS_ON_LEAVE => Employee::statusLabel(Employee::STATUS_ON_LEAVE),
            Employee::STATUS_SUSPENDED => Employee::statusLabel(Employee::STATUS_SUSPENDED),
            Employee::STATUS_RESIGNED => Employee::statusLabel(Employee::STATUS_RESIGNED),
            Employee::STATUS_TERMINATED => Employee::statusLabel(Employee::STATUS_TERMINATED),
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
     * @return list<array{id: int, label: string}>
     */
    public static function loadUserAccountOptions(?int $excludeEmployeeId = null): array
    {
        return \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance()
            ->employees()
            ->listLinkableUserAccountOptions($excludeEmployeeId);
    }

    /**
     * @param list<array{id: int, label: string}> $users
     */
    public static function userAccountOptions(array $users): void
    {
        foreach ($users as $user) {
            printf(
                '<option value="%d">%s</option>',
                (int) $user['id'],
                esc_html($user['label']),
            );
        }
    }

    private static function initials(Employee $employee): string
    {
        $first = $employee->firstName !== '' ? strtoupper($employee->firstName[0]) : '';
        $last = $employee->lastName !== '' ? strtoupper($employee->lastName[0]) : '';

        $initials = $first . $last;

        return $initials !== '' ? $initials : '?';
    }
}

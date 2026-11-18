<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequest;
use MF\StaffCore\Modules\LeaveRequests\DTO\LeaveRequestListFilter;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\LeaveRequestAjaxHandler;

final class LeaveRequestsPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->leaveRequests();
        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $scopeEmployeeIds = null;
        $scopeError = null;

        try {
            $scopeEmployeeIds = $container->scopeEmployeeIdsForModule('leave_requests');
        } catch (AuthorizationException $exception) {
            $scopeError = $exception->getMessage();
        }

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $listFilter = self::resolveListFilter($scopeEmployeeIds);
        $filterQuery = $listFilter->toQueryArgs();

        if ($scopeError === null) {
            $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);

            $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
            if ($page !== $result->page) {
                $result = $service->listPage($page, $perPage, $scopeEmployeeIds, $listFilter);
            }

            $leaveRequests = $result->items;
            $currentPage = $result->page;
            $totalPages = $result->totalPages();
            $totalItems = $result->total;
        } else {
            $leaveRequests = [];
            $currentPage = 1;
            $totalPages = 1;
            $totalItems = 0;
        }

        $perPage = $perPage;
        $employees = $scopeError === null ? $service->employeeOptions($scopeEmployeeIds) : [];
        $filterRoute = new AdminRoute('hr-management', 'leave-requests');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $isEmployeeScoped = $container->employeeScopeResolver()->isSingleEmployeeScope($scopeEmployeeIds);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = LeaveRequestAjaxHandler::nonce();
        $scopeError = $scopeError ?? null;
        $perms = $container->authorization()->modulePermissions('leave_requests');
        $canView = $perms->view;
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $canApprove = $canEdit && ! $isEmployeeScoped;
        $showRowViewActions = $canView;
        $bulkDeletePermission = 'leave_requests.delete';
        $bulkDeleteModalId = 'staffcore-leave_requests-bulk-delete-modal';
        $bulkEntityPlural = __('leave requests', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_leave_request_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/LeaveRequests/Views/page.php';
    }

    public static function resolveListFilter(?array $scopeEmployeeIds = null): LeaveRequestListFilter
    {
        $employeeId = isset($_GET[LeaveRequestListFilter::QUERY_EMPLOYEE])
            ? (int) $_GET[LeaveRequestListFilter::QUERY_EMPLOYEE]
            : 0;
        $status = isset($_GET[LeaveRequestListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[LeaveRequestListFilter::QUERY_STATUS]))
            : '';
        $type = isset($_GET[LeaveRequestListFilter::QUERY_TYPE])
            ? sanitize_text_field(wp_unslash((string) $_GET[LeaveRequestListFilter::QUERY_TYPE]))
            : '';
        $dateFrom = isset($_GET[LeaveRequestListFilter::QUERY_DATE_FROM])
            ? sanitize_text_field(wp_unslash((string) $_GET[LeaveRequestListFilter::QUERY_DATE_FROM]))
            : '';
        $dateTo = isset($_GET[LeaveRequestListFilter::QUERY_DATE_TO])
            ? sanitize_text_field(wp_unslash((string) $_GET[LeaveRequestListFilter::QUERY_DATE_TO]))
            : '';

        if ($scopeEmployeeIds !== null && count($scopeEmployeeIds) === 1) {
            $employeeId = $scopeEmployeeIds[0];
        }

        return new LeaveRequestListFilter(
            employeeId: $employeeId > 0 ? $employeeId : null,
            status: $status !== '' && $status !== 'all' ? strtoupper($status) : null,
            leaveType: $type !== '' && $type !== 'all' ? $type : null,
            dateFrom: $dateFrom !== '' ? $dateFrom : null,
            dateTo: $dateTo !== '' ? $dateTo : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            LeaveRequest::STATUS_PENDING => '<span class="badge bg-warning-lt">'
                . esc_html__('Pending', 'mf-staffcore-lite') . '</span>',
            LeaveRequest::STATUS_APPROVED => '<span class="badge bg-success-lt">'
                . esc_html__('Approved', 'mf-staffcore-lite') . '</span>',
            LeaveRequest::STATUS_REJECTED => '<span class="badge bg-danger-lt">'
                . esc_html__('Rejected', 'mf-staffcore-lite') . '</span>',
            LeaveRequest::STATUS_CANCELLED => '<span class="badge bg-secondary-lt">'
                . esc_html__('Cancelled', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($status) . '</span>',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function leaveRequestPayload(LeaveRequest $leaveRequest): array
    {
        return $leaveRequest->toArray();
    }

    public static function leaveTypeOptions(): void
    {
        foreach (LeaveRequest::leaveTypes() as $type) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($type),
                esc_html($type),
            );
        }
    }

    public static function statusOptions(): void
    {
        $options = [
            LeaveRequest::STATUS_PENDING => __('Pending', 'mf-staffcore-lite'),
            LeaveRequest::STATUS_APPROVED => __('Approved', 'mf-staffcore-lite'),
            LeaveRequest::STATUS_REJECTED => __('Rejected', 'mf-staffcore-lite'),
            LeaveRequest::STATUS_CANCELLED => __('Cancelled', 'mf-staffcore-lite'),
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
            LeaveRequest::STATUS_PENDING => __('Pending', 'mf-staffcore-lite'),
            LeaveRequest::STATUS_APPROVED => __('Approved', 'mf-staffcore-lite'),
            LeaveRequest::STATUS_REJECTED => __('Rejected', 'mf-staffcore-lite'),
            LeaveRequest::STATUS_CANCELLED => __('Cancelled', 'mf-staffcore-lite'),
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

    public static function leaveTypeFilterOptions(?string $selected): void
    {
        printf(
            '<option value="all">%s</option>',
            esc_html__('All Types', 'mf-staffcore-lite'),
        );

        foreach (LeaveRequest::leaveTypes() as $type) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($type),
                selected($selected ?? 'all', $type, false),
                esc_html($type),
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

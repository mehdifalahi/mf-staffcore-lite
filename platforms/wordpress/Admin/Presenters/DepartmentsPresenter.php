<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\DepartmentAjaxHandler;

final class DepartmentsPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->departments();
        $pagination = new ListTablePagination(new AdminUrlBuilder());

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $service->listPage($page, $perPage);

        $page = $pagination->redirectIfPageOutOfRange($page, $result);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage);
        }

        $departments = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $managers = $service->managerOptions();
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = DepartmentAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('departments');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'departments.delete';
        $bulkDeleteModalId = 'staffcore-departments-bulk-delete-modal';
        $bulkEntityPlural = __('departments', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_department_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/Departments/Views/page.php';
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function statusBadge(string $status): string
    {
        if ($status === Department::STATUS_ACTIVE) {
            return '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>';
        }

        return '<span class="badge bg-secondary-lt">' . esc_html__('Inactive', 'mf-staffcore-lite') . '</span>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function departmentPayload(Department $department): array
    {
        return $department->toArray();
    }

    /**
     * @param list<array{id: int, label: string}> $managers
     */
    public static function managerOptions(array $managers): void
    {
        foreach ($managers as $manager) {
            printf(
                '<option value="%d">%s</option>',
                (int) $manager['id'],
                esc_html($manager['label']),
            );
        }
    }
}

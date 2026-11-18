<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\Maintenance\DTO\Maintenance;
use MF\StaffCore\Modules\Maintenance\DTO\MaintenanceListFilter;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\MaintenanceAjaxHandler;

final class MaintenancePresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->maintenance();
        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $listFilter = self::resolveListFilter();

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $service->listPage($page, $perPage, $listFilter);
        $filterQuery = $listFilter->toQueryArgs();

        $page = $pagination->redirectIfPageOutOfRange($page, $result, $filterQuery);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage, $listFilter);
        }

        $maintenanceRecords = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $assets = $service->assetOptions();
        $employees = $service->employeeOptions();
        $filterRoute = new AdminRoute('assets', 'maintenance');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = MaintenanceAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('maintenance');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'maintenance.delete';
        $bulkDeleteModalId = 'staffcore-maintenance-bulk-delete-modal';
        $bulkEntityPlural = __('maintenance records', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_maintenance_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/Maintenance/Views/page.php';
    }

    public static function resolveListFilter(): MaintenanceListFilter
    {
        $assetId = isset($_GET[MaintenanceListFilter::QUERY_ASSET])
            ? absint(wp_unslash($_GET[MaintenanceListFilter::QUERY_ASSET]))
            : 0;
        $type = isset($_GET[MaintenanceListFilter::QUERY_TYPE])
            ? sanitize_key(wp_unslash((string) $_GET[MaintenanceListFilter::QUERY_TYPE]))
            : '';
        $employeeId = isset($_GET[MaintenanceListFilter::QUERY_EMPLOYEE])
            ? absint(wp_unslash($_GET[MaintenanceListFilter::QUERY_EMPLOYEE]))
            : 0;
        $dateFrom = isset($_GET[MaintenanceListFilter::QUERY_DATE_FROM])
            ? sanitize_text_field(wp_unslash((string) $_GET[MaintenanceListFilter::QUERY_DATE_FROM]))
            : '';
        $dateTo = isset($_GET[MaintenanceListFilter::QUERY_DATE_TO])
            ? sanitize_text_field(wp_unslash((string) $_GET[MaintenanceListFilter::QUERY_DATE_TO]))
            : '';

        return new MaintenanceListFilter(
            assetId: $assetId > 0 ? $assetId : null,
            type: $type !== '' && $type !== 'all' ? strtoupper($type) : null,
            performedByEmployeeId: $employeeId > 0 ? $employeeId : null,
            dateFrom: $dateFrom !== '' ? $dateFrom : null,
            dateTo: $dateTo !== '' ? $dateTo : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function formatMoney(?float $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        return esc_html(number_format($amount, 2));
    }

    public static function typeBadge(string $type): string
    {
        return match ($type) {
            Maintenance::TYPE_REPAIR => '<span class="badge bg-danger-lt">'
                . esc_html__('Repair', 'mf-staffcore-lite') . '</span>',
            Maintenance::TYPE_SERVICE => '<span class="badge bg-azure-lt">'
                . esc_html__('Service', 'mf-staffcore-lite') . '</span>',
            Maintenance::TYPE_INSPECTION => '<span class="badge bg-warning-lt">'
                . esc_html__('Inspection', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($type) . '</span>',
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            Maintenance::TYPE_REPAIR => __('Repair', 'mf-staffcore-lite'),
            Maintenance::TYPE_SERVICE => __('Service', 'mf-staffcore-lite'),
            Maintenance::TYPE_INSPECTION => __('Inspection', 'mf-staffcore-lite'),
            default => $type,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function maintenancePayload(Maintenance $maintenance): array
    {
        return $maintenance->toArray();
    }

    public static function typeOptions(): void
    {
        $options = [
            Maintenance::TYPE_REPAIR => __('Repair', 'mf-staffcore-lite'),
            Maintenance::TYPE_SERVICE => __('Service', 'mf-staffcore-lite'),
            Maintenance::TYPE_INSPECTION => __('Inspection', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label),
            );
        }
    }

    public static function typeFilterOptions(?string $selected): void
    {
        $options = [
            'all' => __('All Types', 'mf-staffcore-lite'),
            Maintenance::TYPE_REPAIR => __('Repair', 'mf-staffcore-lite'),
            Maintenance::TYPE_SERVICE => __('Service', 'mf-staffcore-lite'),
            Maintenance::TYPE_INSPECTION => __('Inspection', 'mf-staffcore-lite'),
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
     * @param list<array{id: int, label: string}> $assets
     */
    public static function assetOptions(array $assets, ?int $selectedId = null): void
    {
        foreach ($assets as $asset) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $asset['id'],
                selected($selectedId, (int) $asset['id'], false),
                esc_html($asset['label']),
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

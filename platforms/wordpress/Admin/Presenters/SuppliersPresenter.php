<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\Suppliers\DTO\Supplier;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\SupplierAjaxHandler;

final class SuppliersPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->suppliers();
        $pagination = new ListTablePagination(new AdminUrlBuilder());

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $service->listPage($page, $perPage);

        $page = $pagination->redirectIfPageOutOfRange($page, $result);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage);
        }

        $suppliers = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = SupplierAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('suppliers');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'suppliers.delete';
        $bulkDeleteModalId = 'staffcore-suppliers-bulk-delete-modal';
        $bulkEntityPlural = __('suppliers', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_supplier_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/Suppliers/Views/page.php';
    }

    public static function statusBadge(string $status): string
    {
        if ($status === Supplier::STATUS_ACTIVE) {
            return '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>';
        }

        return '<span class="badge bg-secondary-lt">' . esc_html__('Inactive', 'mf-staffcore-lite') . '</span>';
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    /**
     * @return array<string, mixed>
     */
    public static function supplierPayload(Supplier $supplier): array
    {
        return $supplier->toArray();
    }
}

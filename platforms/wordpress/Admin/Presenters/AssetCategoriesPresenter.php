<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\AssetCategories\DTO\AssetCategory;
use MF\StaffCore\Modules\AssetCategories\Services\AssetCategoryService;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\AssetCategoryAjaxHandler;
use MF\StaffCore\Support\PaginatedList;

final class AssetCategoriesPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->assetCategories();
        $pagination = new ListTablePagination(new AdminUrlBuilder());

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $service->listPage($page, $perPage);

        $page = $pagination->redirectIfPageOutOfRange($page, $result);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage);
        }

        $categories = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = AssetCategoryAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('asset_categories');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'asset_categories.delete';
        $bulkDeleteModalId = 'staffcore-asset_categories-bulk-delete-modal';
        $bulkEntityPlural = __('asset categories', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_asset_category_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/AssetCategories/Views/page.php';
    }

    public static function formatDateTime(string $datetime): string
    {
        if ($datetime === '') {
            return '—';
        }

        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return $datetime;
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }

    /**
     * @return array<string, mixed>
     */
    public static function categoryPayload(AssetCategory $category): array
    {
        $payload = $category->toArray();
        $payload['updated_at_display'] = self::formatDateTime($category->updatedAt);

        return $payload;
    }
}

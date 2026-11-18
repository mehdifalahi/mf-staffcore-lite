<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\AssetCategories\DTO\AssetCategory;
use MF\StaffCore\Modules\Assets\DTO\Asset;
use MF\StaffCore\Modules\Assets\DTO\AssetListFilter;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Modules\Suppliers\DTO\Supplier;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\AssetAjaxHandler;

final class AssetsPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->assets();
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

        $assets = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $categories = $container->assetCategories()->listAll();
        $suppliers = $container->suppliers()->listAll();
        $employees = $service->employeeOptions();
        $filterRoute = new AdminRoute('assets', 'assets');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = AssetAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('assets');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'assets.delete';
        $bulkDeleteModalId = 'staffcore-assets-bulk-delete-modal';
        $bulkEntityPlural = __('assets', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_asset_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/Assets/Views/page.php';
    }

    public static function resolveListFilter(): AssetListFilter
    {
        $categoryId = isset($_GET[AssetListFilter::QUERY_CATEGORY])
            ? (int) $_GET[AssetListFilter::QUERY_CATEGORY]
            : 0;
        $status = isset($_GET[AssetListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[AssetListFilter::QUERY_STATUS]))
            : '';
        $assignedToEmployeeId = isset($_GET[AssetListFilter::QUERY_ASSIGNED_TO])
            ? (int) $_GET[AssetListFilter::QUERY_ASSIGNED_TO]
            : 0;
        $search = isset($_GET[AssetListFilter::QUERY_SEARCH])
            ? sanitize_text_field(wp_unslash((string) $_GET[AssetListFilter::QUERY_SEARCH]))
            : '';

        return new AssetListFilter(
            categoryId: $categoryId > 0 ? $categoryId : null,
            status: $status !== '' && $status !== 'all' ? strtoupper($status) : null,
            assignedToEmployeeId: $assignedToEmployeeId > 0 ? $assignedToEmployeeId : null,
            search: trim($search) !== '' ? trim($search) : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function formatMoney(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return esc_html(number_format((float) $value, 2));
    }

    public static function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(wp_date('M d, Y', $timestamp));
    }

    public static function purchaseDateLine(?string $date): string
    {
        $formatted = self::formatDate($date);

        if ($formatted === '') {
            return '';
        }

        return sprintf(
            /* translators: %s: purchase date */
            esc_html__('Purchased On %s', 'mf-staffcore-lite'),
            $formatted,
        );
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            Asset::STATUS_ACTIVE => '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>',
            Asset::STATUS_UNDER_REPAIR => '<span class="badge bg-warning-lt">' . esc_html__('Under repair', 'mf-staffcore-lite') . '</span>',
            Asset::STATUS_DISPOSED => '<span class="badge bg-danger-lt">' . esc_html__('Disposed', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html__('Inactive', 'mf-staffcore-lite') . '</span>',
        };
    }

    public static function categoryBadge(?string $name): string
    {
        if ($name === null || $name === '') {
            return '—';
        }

        return '<span class="badge bg-secondary-lt">' . esc_html($name) . '</span>';
    }

    /**
     * @param list<AssetCategory> $categories
     */
    public static function categoryFilterOptions(array $categories, ?int $selectedId): void
    {
        printf(
            '<option value="">%s</option>',
            esc_html__('All Categories', 'mf-staffcore-lite'),
        );

        foreach ($categories as $category) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $category->id,
                selected($selectedId, $category->id, false),
                esc_html($category->name),
            );
        }
    }

    public static function statusFilterOptions(?string $selected): void
    {
        $options = [
            'all' => __('All Statuses', 'mf-staffcore-lite'),
            Asset::STATUS_ACTIVE => __('Active', 'mf-staffcore-lite'),
            Asset::STATUS_INACTIVE => __('Inactive', 'mf-staffcore-lite'),
            Asset::STATUS_UNDER_REPAIR => __('Under repair', 'mf-staffcore-lite'),
            Asset::STATUS_DISPOSED => __('Disposed', 'mf-staffcore-lite'),
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

    public static function conditionLabel(string $condition): string
    {
        return match ($condition) {
            Asset::CONDITION_EXCELLENT => esc_html__('Excellent', 'mf-staffcore-lite'),
            Asset::CONDITION_GOOD => esc_html__('Good', 'mf-staffcore-lite'),
            Asset::CONDITION_FAIR => esc_html__('Fair', 'mf-staffcore-lite'),
            Asset::CONDITION_POOR => esc_html__('Poor', 'mf-staffcore-lite'),
            default => esc_html($condition),
        };
    }

    public static function assignedToLabel(?string $name): string
    {
        if ($name === null || $name === '') {
            return '—';
        }

        return esc_html(strtoupper($name));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function nameCellHtml(Asset $asset, array $payload): string
    {
        $html = '<div class="d-flex align-items-center gap-2 staffcore-asset-name-cell">';

        if ($asset->image !== null && $asset->image !== '') {
            $html .= sprintf(
                '<span class="avatar avatar-sm rounded staffcore-asset-thumb" style="background-image: url(%s)" role="img" aria-label="%s"></span>',
                esc_url($asset->image),
                esc_attr($asset->name),
            );
        }

        $html .= '<div>';
        $html .= '<div class="fw-medium">' . esc_html($asset->name) . '</div>';

        if (($payload['purchase_date_line'] ?? '') !== '') {
            $html .= '<div class="text-secondary small">' . esc_html((string) $payload['purchase_date_line']) . '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    public static function imageLinkLabel(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        $basename = $path !== null ? basename($path) : $url;

        return $basename !== '' ? $basename : $url;
    }

    /**
     * @return array<string, mixed>
     */
    public static function assetPayload(Asset $asset): array
    {
        $payload = $asset->toArray();
        $payload['purchase_date_display'] = self::formatDate($asset->purchaseDate);
        $payload['purchase_date_line'] = self::purchaseDateLine($asset->purchaseDate);
        $payload['current_value_display'] = self::formatMoney($asset->currentValue);
        $payload['condition_label'] = self::conditionLabel($asset->assetCondition);
        $payload['assigned_to_display'] = $asset->assignedToName !== null && $asset->assignedToName !== ''
            ? strtoupper($asset->assignedToName)
            : '';

        return $payload;
    }

    /**
     * @param list<AssetCategory> $categories
     */
    public static function categoryOptions(array $categories): void
    {
        foreach ($categories as $category) {
            printf(
                '<option value="%d">%s</option>',
                (int) $category->id,
                esc_html($category->name),
            );
        }
    }

    /**
     * @param list<Supplier> $suppliers
     */
    public static function supplierOptions(array $suppliers): void
    {
        foreach ($suppliers as $supplier) {
            printf(
                '<option value="%d">%s</option>',
                (int) $supplier->id,
                esc_html($supplier->supplierName),
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

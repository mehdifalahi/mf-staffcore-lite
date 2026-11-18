<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Modules\Positions\DTO\Position;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\PositionAjaxHandler;

final class PositionsPresenter
{
    public function render(): void
    {
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $service = $container->positions();
        $pagination = new ListTablePagination(new AdminUrlBuilder());

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $service->listPage($page, $perPage);

        $page = $pagination->redirectIfPageOutOfRange($page, $result);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage);
        }

        $positions = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $perPage;
        $departments = $container->departments()->listActive();
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = PositionAjaxHandler::nonce();
        $perms = $container->authorization()->modulePermissions('positions');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'positions.delete';
        $bulkDeleteModalId = 'staffcore-positions-bulk-delete-modal';
        $bulkEntityPlural = __('positions', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_position_bulk_delete';

        include MF_STAFFCORE_PATH . 'modules/Positions/Views/page.php';
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

    public static function salaryRange(?string $min, ?string $max): string
    {
        if (($min === null || $min === '') && ($max === null || $max === '')) {
            return '—';
        }

        if ($min !== null && $min !== '' && $max !== null && $max !== '') {
            return self::formatMoney($min) . ' – ' . self::formatMoney($max);
        }

        return self::formatMoney($min ?? $max);
    }

    public static function statusBadge(string $status): string
    {
        if ($status === Position::STATUS_ACTIVE) {
            return '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>';
        }

        return '<span class="badge bg-secondary-lt">' . esc_html__('Inactive', 'mf-staffcore-lite') . '</span>';
    }

    public static function levelLabel(string $level): string
    {
        return match ($level) {
            Position::LEVEL_ENTRY => esc_html__('Entry', 'mf-staffcore-lite'),
            Position::LEVEL_JUNIOR => esc_html__('Junior', 'mf-staffcore-lite'),
            Position::LEVEL_MID => esc_html__('Mid', 'mf-staffcore-lite'),
            Position::LEVEL_SENIOR => esc_html__('Senior', 'mf-staffcore-lite'),
            Position::LEVEL_LEAD => esc_html__('Lead', 'mf-staffcore-lite'),
            Position::LEVEL_MANAGER => esc_html__('Manager', 'mf-staffcore-lite'),
            Position::LEVEL_DIRECTOR => esc_html__('Director', 'mf-staffcore-lite'),
            Position::LEVEL_EXECUTIVE => esc_html__('Executive', 'mf-staffcore-lite'),
            default => esc_html($level),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function positionPayload(Position $position): array
    {
        $payload = $position->toArray();
        $payload['level_label'] = self::levelLabel($position->level);
        $payload['salary_range_display'] = self::salaryRange($position->minSalary, $position->maxSalary);

        return $payload;
    }

    /**
     * @param list<Department> $departments
     */
    public static function departmentOptions(array $departments): void
    {
        foreach ($departments as $department) {
            printf(
                '<option value="%d">%s</option>',
                (int) $department->id,
                esc_html($department->name),
            );
        }
    }

    public static function levelOptions(): void
    {
        $levels = [
            Position::LEVEL_ENTRY => __('Entry', 'mf-staffcore-lite'),
            Position::LEVEL_JUNIOR => __('Junior', 'mf-staffcore-lite'),
            Position::LEVEL_MID => __('Mid', 'mf-staffcore-lite'),
            Position::LEVEL_SENIOR => __('Senior', 'mf-staffcore-lite'),
            Position::LEVEL_LEAD => __('Lead', 'mf-staffcore-lite'),
            Position::LEVEL_MANAGER => __('Manager', 'mf-staffcore-lite'),
            Position::LEVEL_DIRECTOR => __('Director', 'mf-staffcore-lite'),
            Position::LEVEL_EXECUTIVE => __('Executive', 'mf-staffcore-lite'),
        ];

        foreach ($levels as $value => $label) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label),
            );
        }
    }
}

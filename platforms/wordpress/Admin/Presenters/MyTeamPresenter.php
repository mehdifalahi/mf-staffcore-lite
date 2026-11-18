<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\MyTeam\DTO\MyTeamMember;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\UI\PersonAvatar;

final class MyTeamPresenter
{
    public function render(): void
    {
        $container = ServiceContainer::instance();
        $auth = $container->authorization();
        $context = $auth->current();

        if ($context === null || $context->role !== RoleRegistry::ROLE_HR_MANAGER) {
            echo '<div class="alert alert-warning" role="alert">';
            esc_html_e('This page is only available for managers.', 'mf-staffcore-lite');
            echo '</div>';

            return;
        }

        $urlBuilder = new AdminUrlBuilder();
        $pagination = new ListTablePagination($urlBuilder);
        $service = $container->myTeam();
        $overview = $service->overview($context->platformUserId);

        $page = ListTablePagination::resolvePage();
        $perPage = ListTablePagination::perPage();
        $result = $service->listPage($context->platformUserId, $page, $perPage);

        $page = $pagination->redirectIfPageOutOfRange($page, $result);
        if ($page !== $result->page) {
            $result = $service->listPage($page, $perPage);
        }

        $members = $result->items;
        $departments = $overview->departments;
        $stats = [
            'total' => $overview->totalEmployees,
            'active' => $overview->activeEmployees,
            'on_leave' => $overview->onLeaveEmployees,
        ];
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $perPage = $result->perPage;

        include MF_STAFFCORE_PATH . 'modules/MyTeam/Views/page.php';
    }

    public static function employeeCellHtml(MyTeamMember $member): string
    {
        $name = trim($member->fullName());

        return PersonAvatar::cellHtml(
            $name !== '' ? $name : '—',
            $member->photo,
        );
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            Employee::STATUS_ACTIVE => '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>',
            Employee::STATUS_ON_LEAVE => '<span class="badge bg-warning-lt">' . esc_html__('On Leave', 'mf-staffcore-lite') . '</span>',
            Employee::STATUS_SUSPENDED => '<span class="badge bg-secondary-lt">' . esc_html__('Suspended', 'mf-staffcore-lite') . '</span>',
            Employee::STATUS_RESIGNED => '<span class="badge bg-secondary-lt">' . esc_html__('Resigned', 'mf-staffcore-lite') . '</span>',
            Employee::STATUS_TERMINATED => '<span class="badge bg-danger-lt">' . esc_html__('Terminated', 'mf-staffcore-lite') . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($status) . '</span>',
        };
    }

    public static function formatHireDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(gmdate('M j, Y', $timestamp));
    }
}

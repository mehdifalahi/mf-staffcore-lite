<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Auth\PlatformUser;
use MF\StaffCore\Authorization\ManagerScopeModeRegistry;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Users\DTO\BulkLinkResult;
use MF\StaffCore\Modules\Users\DTO\LinkCandidate;
use MF\StaffCore\Modules\Users\DTO\StaffUser;
use MF\StaffCore\Modules\Users\DTO\UserListFilter;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\ListTablePagination;
use MF\StaffCore\Platforms\WordPress\Ajax\UsersAjaxHandler;
use MF\StaffCore\UI\PersonAvatar;
use MF\StaffCore\UI\TablerIcon;

final class UsersPresenter
{
    public function render(): void
    {
        $service = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance()->staffUsers();
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

        $users = $result->items;
        $currentPage = $result->page;
        $totalPages = $result->totalPages();
        $totalItems = $result->total;
        $filterRoute = new AdminRoute('settings', 'users');
        $filterActionUrl = $urlBuilder->build($filterRoute);
        $ajaxUrl = admin_url('admin-ajax.php');
        $nonce = UsersAjaxHandler::nonce();
        $container = \MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer::instance();
        $perms = $container->authorization()->modulePermissions('users');
        $canCreate = $perms->create;
        $canEdit = $perms->edit;
        $canDelete = $perms->delete;
        $bulkDeletePermission = 'users.delete';
        $bulkDeleteModalId = 'staffcore-users-bulk-delete-modal';
        $bulkEntityPlural = __('users', 'mf-staffcore-lite');
        $bulkDeleteAction = 'mf_staffcore_users_bulk_delete';
        $userAvatarPhotos = self::avatarPhotosForUsers($users, $container->employees());

        include MF_STAFFCORE_PATH . 'modules/Users/Views/page.php';
    }

    public static function resolveListFilter(): UserListFilter
    {
        $role = isset($_GET[UserListFilter::QUERY_ROLE])
            ? sanitize_key((string) wp_unslash($_GET[UserListFilter::QUERY_ROLE]))
            : '';

        $status = isset($_GET[UserListFilter::QUERY_STATUS])
            ? sanitize_key((string) wp_unslash($_GET[UserListFilter::QUERY_STATUS]))
            : '';

        $search = isset($_GET[UserListFilter::QUERY_SEARCH])
            ? sanitize_text_field(wp_unslash((string) $_GET[UserListFilter::QUERY_SEARCH]))
            : '';

        $search = trim($search);

        return new UserListFilter(
            role: $role !== '' && $role !== 'all' && RoleRegistry::isValidRole($role)
                ? $role
                : null,
            status: $status !== '' && $status !== 'all'
                && in_array($status, [StaffUser::STATUS_ACTIVE, StaffUser::STATUS_INACTIVE], true)
                ? $status
                : null,
            search: $search !== '' ? $search : null,
        );
    }

    public static function displayValue(?string $value): string
    {
        return $value !== null && $value !== '' ? esc_html($value) : '—';
    }

    public static function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return esc_html($date);
        }

        return esc_html(gmdate('Y-m-d', $timestamp));
    }

    public static function userCellHtml(StaffUser $user, ?string $photoUrl = null): string
    {
        $name = $user->displayName ?? '';

        return PersonAvatar::cellHtml(
            $name,
            $photoUrl,
            $user->email ?? '',
        );
    }

    /**
     * @param list<StaffUser> $users
     * @return array<int, string> staff_user.id => photo URL
     */
    public static function avatarPhotosForUsers(array $users, \MF\StaffCore\Modules\Employees\Services\EmployeeService $employees): array
    {
        if ($users === []) {
            return [];
        }

        $platformIds = array_map(
            static fn (StaffUser $user): int => $user->platformUserId,
            $users,
        );
        $employeePhotos = $employees->photosByPlatformUserIds($platformIds);
        $photos = [];

        foreach ($users as $user) {
            $platformUserId = $user->platformUserId;

            if (isset($employeePhotos[$platformUserId])) {
                $photos[$user->id] = $employeePhotos[$platformUserId];

                continue;
            }

            if (function_exists('get_avatar_url')) {
                $avatar = get_avatar_url($platformUserId, ['size' => 96]);

                if (is_string($avatar) && $avatar !== '') {
                    $photos[$user->id] = $avatar;
                }
            }
        }

        return $photos;
    }

    public static function roleBadge(string $role): string
    {
        $label = RoleRegistry::displayLabel($role);

        return match ($role) {
            RoleRegistry::ROLE_ADMINISTRATOR => '<span class="badge bg-danger-lt">'
                . TablerIcon::icon('shield', 'me-1')
                . esc_html($label) . '</span>',
            RoleRegistry::ROLE_HR_MANAGER => '<span class="badge bg-info-lt">'
                . TablerIcon::icon('user-shield', 'me-1')
                . esc_html($label) . '</span>',
            RoleRegistry::ROLE_EMPLOYEE => '<span class="badge bg-secondary-lt">'
                . TablerIcon::icon('user', 'me-1')
                . esc_html($label) . '</span>',
            default => '<span class="badge bg-secondary-lt">' . esc_html($label) . '</span>',
        };
    }

    public static function loginAttemptsBadge(StaffUser $user): string
    {
        $failed = $user->failedLoginAttempts;

        if ($failed <= 0) {
            return '<span class="badge bg-success-lt">0</span>';
        }

        return '<span class="badge bg-danger-lt">'
            . esc_html(sprintf(
                /* translators: %d: failed login count */
                _n('%d Failed', '%d Failed', $failed, 'mf-staffcore-lite'),
                $failed,
            ))
            . '</span>';
    }

    public static function statusBadge(string $status): string
    {
        if ($status === StaffUser::STATUS_ACTIVE) {
            return '<span class="badge bg-success-lt">' . esc_html__('Active', 'mf-staffcore-lite') . '</span>';
        }

        return '<span class="badge bg-danger-lt">' . esc_html__('Inactive', 'mf-staffcore-lite') . '</span>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function userPayload(StaffUser $user): array
    {
        return $user->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function platformUserPayload(PlatformUser $user): array
    {
        return [
            'id' => $user->id,
            'login' => $user->login,
            'email' => $user->email,
            'display_name' => $user->displayName,
            'label' => $user->displayName . ' (' . $user->login . ')',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function linkCandidatePayload(LinkCandidate $candidate): array
    {
        return $candidate->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function bulkLinkResultPayload(BulkLinkResult $result): array
    {
        return $result->toArray();
    }

    public static function bulkLinkSummaryMessage(BulkLinkResult $result): string
    {
        $linked = count($result->linked);
        $skipped = count($result->skipped);
        $failed = count($result->failed);

        if ($failed === 0 && $skipped === 0) {
            return sprintf(
                /* translators: %d: number of users linked */
                _n('%d user linked successfully.', '%d users linked successfully.', $linked, 'mf-staffcore-lite'),
                $linked,
            );
        }

        $parts = [];

        if ($linked > 0) {
            $parts[] = sprintf(
                /* translators: %d: linked count */
                _n('%d linked', '%d linked', $linked, 'mf-staffcore-lite'),
                $linked,
            );
        }

        if ($skipped > 0) {
            $parts[] = sprintf(
                /* translators: %d: skipped count */
                _n('%d skipped', '%d skipped', $skipped, 'mf-staffcore-lite'),
                $skipped,
            );
        }

        if ($failed > 0) {
            $parts[] = sprintf(
                /* translators: %d: failed count */
                _n('%d failed', '%d failed', $failed, 'mf-staffcore-lite'),
                $failed,
            );
        }

        return implode(', ', $parts) . '.';
    }

    public static function roleOptions(?string $selected = null): void
    {
        foreach (RoleRegistry::roles() as $role) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) $role['id']),
                selected($selected, (string) $role['id'], false),
                esc_html(RoleRegistry::displayLabel((string) $role['id'])),
            );
        }
    }

    public static function roleFilterOptions(?string $selected = null): void
    {
        printf(
            '<option value="all"%s>%s</option>',
            selected($selected ?? 'all', 'all', false),
            esc_html__('All Roles', 'mf-staffcore-lite'),
        );

        self::roleOptions($selected !== null && $selected !== 'all' ? $selected : null);
    }

    public static function statusOptions(?string $selected = null): void
    {
        $options = [
            StaffUser::STATUS_ACTIVE => __('Active', 'mf-staffcore-lite'),
            StaffUser::STATUS_INACTIVE => __('Inactive', 'mf-staffcore-lite'),
        ];

        foreach ($options as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($selected ?? StaffUser::STATUS_ACTIVE, $value, false),
                esc_html($label),
            );
        }
    }

    public static function statusFilterOptions(?string $selected = null): void
    {
        $options = [
            'all' => __('All Statuses', 'mf-staffcore-lite'),
            StaffUser::STATUS_ACTIVE => __('Active', 'mf-staffcore-lite'),
            StaffUser::STATUS_INACTIVE => __('Inactive', 'mf-staffcore-lite'),
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

    public static function managerScopeModeOptions(?string $selected = null): void
    {
        $selected = $selected ?? ManagerScopeModeRegistry::AUTO;

        foreach (ManagerScopeModeRegistry::options() as $option) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($option['value']),
                selected($selected, $option['value'], false),
                esc_html(self::managerScopeModeLabel($option['value'])),
            );
        }
    }

    public static function managerScopeHelpHtml(): string
    {
        $items = '';

        foreach (ManagerScopeModeRegistry::options() as $option) {
            $items .= sprintf(
                '<li><strong>%s</strong> — %s</li>',
                esc_html(self::managerScopeModeLabel($option['value'])),
                esc_html(self::managerScopeModeDescription($option['value'])),
            );
        }

        return sprintf(
            '<p class="form-text text-secondary mb-1">%s</p><ul class="form-text text-secondary mb-0 ps-3">%s</ul>',
            esc_html__(
                'Controls whether this Manager sees company-wide HR data or only their team\'s records. Organizational department assignments and My Team roster are unchanged.',
                'mf-staffcore-lite',
            ),
            $items,
        );
    }

    private static function managerScopeModeLabel(string $value): string
    {
        return match ($value) {
            ManagerScopeModeRegistry::AUTO => __('Automatic (inactive)', 'mf-staffcore-lite'),
            ManagerScopeModeRegistry::TEAM => __('Team only', 'mf-staffcore-lite'),
            ManagerScopeModeRegistry::ALL => __('Company-wide', 'mf-staffcore-lite'),
            default => $value,
        };
    }

    private static function managerScopeModeDescription(string $value): string
    {
        return match ($value) {
            ManagerScopeModeRegistry::AUTO => __(
                'Uses department manager link: team scope when they manage a department, otherwise company-wide.',
                'mf-staffcore-lite',
            ),
            ManagerScopeModeRegistry::TEAM => __(
                'Always limit operational lists to their managed team; lists stay empty until a department manager link exists.',
                'mf-staffcore-lite',
            ),
            ManagerScopeModeRegistry::ALL => __(
                'Full company HR data like central HR staff; My Team roster still follows department assignments only.',
                'mf-staffcore-lite',
            ),
            default => '',
        };
    }

    private static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $letters .= strtoupper($part[0]);

            if (strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '?';
    }
}

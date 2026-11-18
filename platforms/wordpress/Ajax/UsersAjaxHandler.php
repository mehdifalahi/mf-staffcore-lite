<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Auth\PlatformUser;
use MF\StaffCore\Authorization\ManagerScopeModeRegistry;
use MF\StaffCore\Modules\Users\DTO\BulkLinkInput;
use MF\StaffCore\Modules\Users\DTO\LinkCandidateFilter;
use MF\StaffCore\Modules\Users\DTO\StaffUserInput;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\UsersPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class UsersAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;
    use StaffCorePlanAjaxResponse;

    private const NONCE_ACTION = 'mf_staffcore_users';
    private const MODULE = 'users';

    public function searchPlatformUsers(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        $query = isset($_POST['q'])
            ? sanitize_text_field(wp_unslash((string) $_POST['q']))
            : '';

        $users = $this->service()->searchLinkablePlatformUsers($query, 25);

        wp_send_json_success([
            'users' => array_map(
                static fn (PlatformUser $user): array => UsersPresenter::platformUserPayload($user),
                $users,
            ),
        ]);
    }

    public function link(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleCreate(self::MODULE, self::NONCE_ACTION);

        try {
            $staffUser = $this->service()->linkExisting(
                $this->readLinkInputFromPost(),
                get_current_user_id(),
            );

            wp_send_json_success([
                'user' => UsersPresenter::userPayload($staffUser),
                'message' => __('User linked successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function listLinkCandidates(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleView(self::MODULE, self::NONCE_ACTION);

        try {
            $filter = LinkCandidateFilter::fromArray([
                'source' => isset($_POST['source'])
                    ? sanitize_key(wp_unslash((string) $_POST['source']))
                    : LinkCandidateFilter::DEFAULT_SOURCE,
                'search' => isset($_POST['search'])
                    ? sanitize_text_field(wp_unslash((string) $_POST['search']))
                    : '',
                'page' => isset($_POST['page']) ? (int) $_POST['page'] : 1,
                'per_page' => isset($_POST['per_page']) ? (int) $_POST['per_page'] : 10,
            ]);

            $result = $this->service()->listLinkCandidates($filter);

            wp_send_json_success([
                'candidates' => array_map(
                    static fn ($candidate): array => UsersPresenter::linkCandidatePayload($candidate),
                    $result->items,
                ),
                'pagination' => [
                    'page' => $result->page,
                    'per_page' => $result->perPage,
                    'total' => $result->total,
                    'total_pages' => $result->totalPages(),
                ],
            ]);
        } catch (ValidationException $exception) {
            $this->sendJsonErrorWithPlanUpgrade($exception, 422);
        } catch (RuntimeException $exception) {
            $this->sendJsonErrorWithPlanUpgrade($exception, 500);
        }
    }

    public function bulkLink(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleCreate(self::MODULE, self::NONCE_ACTION);

        try {
            $ids = $this->readPlatformUserIdsFromPost();
            $linkInput = $this->readLinkInputFromPost();

            $result = $this->service()->bulkLinkExisting(
                new BulkLinkInput(
                    platformUserIds: $ids,
                    role: $linkInput['role'],
                    status: $linkInput['status'],
                    phone: $linkInput['phone'] !== '' ? $linkInput['phone'] : null,
                ),
                get_current_user_id(),
            );

            wp_send_json_success([
                'result' => UsersPresenter::bulkLinkResultPayload($result),
                'message' => UsersPresenter::bulkLinkSummaryMessage($result),
            ]);
        } catch (ValidationException $exception) {
            $this->sendJsonErrorWithPlanUpgrade($exception, 422);
        } catch (RuntimeException $exception) {
            $this->sendJsonErrorWithPlanUpgrade($exception, 500);
        }
    }

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $raw = $this->readInputFromPost();

            if ($id > 0) {
                $staffUser = $this->service()->update($id, $raw, $userId);
                $message = __('User updated successfully.', 'mf-staffcore-lite');
                $warning = $this->service()->managerScopeSaveWarning(
                    new StaffUserInput(
                        role: $staffUser->role,
                        phone: $staffUser->phone,
                        status: $staffUser->status,
                        managerScopeMode: $staffUser->managerScopeMode,
                    ),
                    $staffUser->platformUserId,
                );
            } else {
                $staffUser = $this->service()->create($raw, $userId);
                $message = __('User created successfully.', 'mf-staffcore-lite');
                $warning = null;
            }

            $response = [
                'user' => UsersPresenter::userPayload($staffUser),
                'message' => $message,
            ];

            if ($warning !== null) {
                $response['warning'] = $warning;
            }

            wp_send_json_success($response);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function changePassword(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid user ID.'], 400);
        }

        $password = isset($_POST['password'])
            ? (string) wp_unslash($_POST['password'])
            : '';
        $passwordConfirm = isset($_POST['password_confirm'])
            ? (string) wp_unslash($_POST['password_confirm'])
            : '';

        try {
            $this->service()->changePassword($id, $password, $passwordConfirm);

            wp_send_json_success([
                'message' => __('Password updated successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function resetLoginAttempts(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid user ID.'], 400);
        }

        try {
            $staffUser = $this->service()->resetLoginAttempts($id, get_current_user_id());

            wp_send_json_success([
                'user' => UsersPresenter::userPayload($staffUser),
                'message' => __('Login attempts reset.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function delete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid user ID.'], 400);
        }

        try {
            $this->service()->delete($id);

            wp_send_json_success([
                'message' => __('User deleted successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function bulkDelete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        try {
            $ids = $this->readBulkDeleteIdsFromPost();
            $result = $this->service()->bulkDelete($ids, get_current_user_id());
            $this->sendBulkDeleteResponse(
                $result,
                __('user', 'mf-staffcore-lite'),
                __('users', 'mf-staffcore-lite'),
            );
        } catch (ValidationException | RuntimeException $exception) {
            $this->handleBulkDeleteFailure($exception);
        }
    }

    /**
     * @return list<int>
     *
     * Caller must run authorizeModule* / check_ajax_referer before this helper.
     */
    private function readPlatformUserIdsFromPost(): array
    {
        if (! isset($_POST['platform_user_ids'])) {
            return [];
        }

        $raw = wp_unslash($_POST['platform_user_ids']);

        if (is_string($raw)) {
            $trimmed = trim($raw);

            if ($trimmed === '') {
                return [];
            }

            if (str_starts_with($trimmed, '[')) {
                try {
                    $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    return [];
                }

                $raw = is_array($decoded) ? $decoded : [];
            } else {
                $raw = array_map(
                    static fn (string $part): string => sanitize_text_field(trim($part)),
                    explode(',', $trimmed),
                );
            }
        }

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => absint($id), $raw),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private function readLinkInputFromPost(): array
    {
        return [
            'platform_user_id' => isset($_POST['platform_user_id'])
                ? absint(wp_unslash($_POST['platform_user_id']))
                : 0,
            'role' => isset($_POST['role'])
                ? sanitize_text_field(wp_unslash((string) $_POST['role']))
                : '',
            'phone' => isset($_POST['phone'])
                ? sanitize_text_field(wp_unslash((string) $_POST['phone']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'active',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readInputFromPost(): array
    {
        $input = [
            'user_login' => isset($_POST['user_login'])
                ? sanitize_user(wp_unslash((string) $_POST['user_login']), true)
                : '',
            'user_email' => isset($_POST['user_email'])
                ? sanitize_email(wp_unslash((string) $_POST['user_email']))
                : '',
            'display_name' => isset($_POST['display_name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['display_name']))
                : '',
            'password' => isset($_POST['password'])
                ? (string) wp_unslash($_POST['password'])
                : '',
            'role' => isset($_POST['role'])
                ? sanitize_text_field(wp_unslash((string) $_POST['role']))
                : '',
            'phone' => isset($_POST['phone'])
                ? sanitize_text_field(wp_unslash((string) $_POST['phone']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'active',
        ];

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id > 0) {
            $rawMode = isset($_POST['manager_scope_mode'])
                ? sanitize_key(wp_unslash((string) $_POST['manager_scope_mode']))
                : ManagerScopeModeRegistry::AUTO;

            $input['manager_scope_mode'] = ManagerScopeModeRegistry::isValid($rawMode)
                ? $rawMode
                : ManagerScopeModeRegistry::AUTO;
        }

        return $input;
    }

    private function service(): \MF\StaffCore\Modules\Users\Services\StaffUserService
    {
        return ServiceContainer::instance()->staffUsers();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

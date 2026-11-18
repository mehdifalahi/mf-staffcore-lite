<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\Services;

use MF\StaffCore\Auth\PlatformUser;
use MF\StaffCore\Auth\PlatformUserGateway;
use MF\StaffCore\Auth\PlatformUserInput;
use MF\StaffCore\Authorization\ManagerScopeModeRegistry;
use MF\StaffCore\Authorization\Repositories\TeamScopeRepository;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Employees\Repositories\EmployeeRepository;
use MF\StaffCore\Modules\Users\DTO\BulkLinkInput;
use MF\StaffCore\Modules\Users\DTO\BulkLinkOutcome;
use MF\StaffCore\Modules\Users\DTO\BulkLinkResult;
use MF\StaffCore\Modules\Users\DTO\LinkCandidate;
use MF\StaffCore\Modules\Users\DTO\LinkCandidateFilter;
use MF\StaffCore\Modules\Users\DTO\StaffUser;
use MF\StaffCore\Modules\Users\DTO\StaffUserInput;
use MF\StaffCore\Modules\Users\DTO\UserListFilter;
use MF\StaffCore\Modules\Users\Repositories\StaffUserRepository;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Navigation\PanelActionUrl;
use MF\StaffCore\Plan\PlanRegistry;
use MF\StaffCore\Notification\EventCatalog;
use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Notification\NotificationDispatcher;
use MF\StaffCore\Notification\StaffCoreEvent;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class StaffUserService
{
    private const PHONE_MAX = 20;
    private const LOGIN_MAX = 60;
    private const DISPLAY_NAME_MAX = 250;
    private const PASSWORD_MIN = 8;
    public const MAX_BULK_LINK = 100;

    public function __construct(
        private readonly StaffUserRepository $repository,
        private readonly PlatformUserGateway $platformUsers,
        private readonly EmployeeRepository $employees,
        private readonly TeamScopeRepository $teamScope,
        private readonly NotificationDispatcher $notificationDispatcher,
    ) {
    }

    /**
     * @return PaginatedList<StaffUser>
     */
    public function listPage(
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?UserListFilter $filter = null,
    ): PaginatedList {
        $platformUserIds = null;

        if ($filter !== null && $filter->search !== null) {
            $platformUserIds = $this->platformUsers->findIdsBySearch($filter->search);
        }

        $result = $this->repository->findPage($page, $perPage, $filter, $platformUserIds);
        $platformIds = array_map(
            static fn (StaffUser $user): int => $user->platformUserId,
            $result->items,
        );
        $platformMap = $this->platformUsers->findByIds($platformIds);
        $enriched = array_map(
            function (StaffUser $user) use ($platformMap): StaffUser {
                return $this->enrich($user, $platformMap[$user->platformUserId] ?? null);
            },
            $result->items,
        );

        return new PaginatedList($enriched, $result->total, $result->page, $result->perPage);
    }

    public function findById(int $id): ?StaffUser
    {
        $user = $this->repository->findById($id);

        if ($user === null) {
            return null;
        }

        $platform = $this->platformUsers->findById($user->platformUserId);

        return $this->enrich($user, $platform);
    }

    /**
     * @param array<string, mixed> $raw
     */
    /**
     * @return list<PlatformUser>
     */
    public function searchLinkablePlatformUsers(string $query, int $limit = 25): array
    {
        return $this->platformUsers->search(
            $query,
            $limit,
            $this->repository->listAllPlatformUserIds(),
        );
    }

    /**
     * Link an existing platform account to StaffCore (no new wp_users row).
     *
     * @param array<string, mixed> $raw
     */
    public function linkExisting(array $raw, int $actorUserId): StaffUser
    {
        $platformUserId = isset($raw['platform_user_id']) ? (int) $raw['platform_user_id'] : 0;

        if ($platformUserId <= 0) {
            throw new ValidationException(esc_html('A platform user must be selected.'));
        }

        $staffInput = $this->normalizeStaffInput($this->staffInputFromArray($raw));
        $this->assertStaffInput($staffInput);

        if ($this->repository->platformUserIdExists($platformUserId)) {
            throw new ValidationException(esc_html('This platform user is already registered in StaffCore.'));
        }

        $platformUser = $this->platformUsers->findById($platformUserId);

        if ($platformUser === null) {
            throw new ValidationException(esc_html('Platform user not found.'));
        }

        $this->assertEmployeeProvisioningPrerequisites($staffInput, $platformUser->email);

        $staffUser = $this->repository->create($platformUserId, $staffInput, $actorUserId);
        $this->syncEmployeeProfileForEmployeeRole($staffInput, $platformUser, $actorUserId);
        $this->dispatchProvisioned($staffUser, $actorUserId);

        return $this->enrich($staffUser, $platformUser);
    }

    /**
     * @return PaginatedList<LinkCandidate>
     */
    public function listLinkCandidates(LinkCandidateFilter $filter): PaginatedList
    {
        PlanRegistry::assertCanBulkLink();

        $exclude = $this->repository->listAllPlatformUserIds();

        if ($filter->source === LinkCandidateFilter::SOURCE_UNLINKED_WITH_EMPLOYEE) {
            return $this->listEmployeeLinkCandidates($filter, $exclude);
        }

        return $this->listAllUnlinkedCandidates($filter, $exclude);
    }

    public function bulkLinkExisting(BulkLinkInput $input, int $actorUserId): BulkLinkResult
    {
        PlanRegistry::assertCanBulkLink();

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $input->platformUserIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            throw new ValidationException(esc_html('Select at least one user.'));
        }

        if (count($ids) > self::MAX_BULK_LINK) {
            throw new ValidationException(esc_html('Maximum ' . self::MAX_BULK_LINK . ' users per bulk link request.'));
        }

        $this->assertStaffInput(new StaffUserInput(
            role: $input->role,
            phone: $input->phone,
            status: $input->status,
        ));

        /** @var list<BulkLinkOutcome> $linked */
        $linked = [];
        /** @var list<BulkLinkOutcome> $skipped */
        $skipped = [];
        /** @var list<BulkLinkOutcome> $failed */
        $failed = [];

        foreach ($ids as $platformUserId) {
            if ($platformUserId === 1) {
                $skipped[] = new BulkLinkOutcome(
                    $platformUserId,
                    'Protected administrator account.',
                );

                continue;
            }

            if ($this->repository->platformUserIdExists($platformUserId)) {
                $skipped[] = new BulkLinkOutcome(
                    $platformUserId,
                    'Already registered in StaffCore.',
                );

                continue;
            }

            try {
                $this->linkExisting([
                    'platform_user_id' => $platformUserId,
                    'role' => $input->role,
                    'status' => $input->status,
                    'phone' => $input->phone ?? '',
                ], $actorUserId);

                $linked[] = new BulkLinkOutcome($platformUserId);
            } catch (ValidationException $exception) {
                $failed[] = new BulkLinkOutcome($platformUserId, $exception->getMessage());
            }
        }

        return new BulkLinkResult($linked, $skipped, $failed);
    }

    public function create(array $raw, int $actorUserId): StaffUser
    {
        $platformInput = $this->platformInputFromArray($raw, true);
        $staffInput = $this->normalizeStaffInput($this->staffInputFromArray($raw));

        $this->assertPlatformInput($platformInput, true);
        $this->assertStaffInput($staffInput);
        $this->assertEmployeeProvisioningPrerequisites($staffInput, $platformInput->email);

        if ($this->platformUsers->loginExists($platformInput->login)) {
            throw new ValidationException(esc_html('Username is already in use.'));
        }

        if ($this->platformUsers->emailExists($platformInput->email)) {
            throw new ValidationException(esc_html('Email is already in use.'));
        }

        $platformUser = $this->platformUsers->create($platformInput);

        if ($this->repository->platformUserIdExists($platformUser->id)) {
            throw new ValidationException(esc_html('This platform user is already registered in StaffCore.'));
        }

        $staffUser = $this->repository->create($platformUser->id, $staffInput, $actorUserId);
        $this->syncEmployeeProfileForEmployeeRole($staffInput, $platformUser, $actorUserId);
        $this->dispatchProvisioned($staffUser, $actorUserId);

        return $this->enrich($staffUser, $platformUser);
    }

    /**
     * Non-blocking warning when a manager is saved with team-only scope but no org link.
     */
    public function managerScopeSaveWarning(StaffUserInput $input, int $platformUserId): ?string
    {
        if ($input->role !== RoleRegistry::ROLE_HR_MANAGER) {
            return null;
        }

        if ($input->managerScopeMode !== ManagerScopeModeRegistry::TEAM) {
            return null;
        }

        if ($this->teamScope->isLineManager($platformUserId)) {
            return null;
        }

        return 'This manager has team-only data access but is not assigned as a department manager. '
            . 'Operational lists will stay empty until a department manager link exists, or change the scope to Automatic or Company-wide.';
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function update(int $id, array $raw, int $actorUserId): StaffUser
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('User not found.'));
        }

        $platformInput = $this->platformInputFromArray($raw, false);
        $staffInput = $this->normalizeStaffInput($this->staffInputFromArray($raw, applyScopeMode: true));

        $this->assertPlatformInput($platformInput, false);
        $this->assertStaffInput($staffInput);

        if ($this->platformUsers->loginExists($platformInput->login, $existing->platformUserId)) {
            throw new ValidationException(esc_html('Username is already in use.'));
        }

        if ($this->platformUsers->emailExists($platformInput->email, $existing->platformUserId)) {
            throw new ValidationException(esc_html('Email is already in use.'));
        }

        $platformUser = $this->platformUsers->update($existing->platformUserId, $platformInput);
        $staffUser = $this->repository->update($id, $staffInput, $actorUserId);

        if ($existing->role !== $staffUser->role) {
            $this->dispatchRoleChanged($staffUser, $actorUserId);
        }

        if ($existing->status !== $staffUser->status && $staffUser->status === StaffUser::STATUS_INACTIVE) {
            $this->dispatchDeactivated($staffUser, $actorUserId);
        }

        return $this->enrich($staffUser, $platformUser);
    }

    public function changePassword(int $id, string $password, string $passwordConfirm): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('User not found.'));
        }

        $password = trim($password);

        if ($password === '') {
            throw new ValidationException(esc_html('Password is required.'));
        }

        if (strlen($password) < self::PASSWORD_MIN) {
            throw new ValidationException(esc_html('Password must be at least ' . self::PASSWORD_MIN . ' characters.'));
        }

        if ($password !== trim($passwordConfirm)) {
            throw new ValidationException(esc_html('Password confirmation does not match.'));
        }

        $this->platformUsers->setPassword($existing->platformUserId, $password);
    }

    public function resetLoginAttempts(int $id, int $actorUserId): StaffUser
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('User not found.'));
        }

        $staffUser = $this->repository->resetLoginAttempts($id, $actorUserId);
        $platform = $this->platformUsers->findById($staffUser->platformUserId);

        if ($platform !== null) {
            $this->dispatchLoginAttemptsReset($staffUser, $platform, $actorUserId);
        }

        return $this->enrich($staffUser, $platform);
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('User not found.'));
        }

        if ($existing->isProtected()) {
            throw new ValidationException(esc_html('This system administrator account cannot be deleted.'));
        }

        try {
            $this->platformUsers->delete($existing->platformUserId);
        } catch (\RuntimeException $exception) {
            throw new ValidationException( esc_html( $exception->getMessage() ) );
        }

        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     */
    public function bulkDelete(array $ids, int $actorUserId): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute(
            $ids,
            fn (int $id) => $this->delete($id),
            function (int $id): ?string {
                $user = $this->repository->findById($id);

                if ($user !== null && $user->isProtected()) {
                    return 'This system administrator account cannot be deleted.';
                }

                return null;
            },
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function platformInputFromArray(array $raw, bool $isCreate): PlatformUserInput
    {
        $password = isset($raw['password']) ? (string) $raw['password'] : null;
        if ($password === '') {
            $password = null;
        }

        if (! $isCreate) {
            $password = null;
        }

        return new PlatformUserInput(
            login: (string) ($raw['user_login'] ?? ''),
            email: (string) ($raw['user_email'] ?? ''),
            displayName: (string) ($raw['display_name'] ?? ''),
            password: $password,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function staffInputFromArray(array $raw, bool $applyScopeMode = false): StaffUserInput
    {
        $phone = isset($raw['phone']) ? (string) $raw['phone'] : null;
        if ($phone === '') {
            $phone = null;
        }

        $role = (string) ($raw['role'] ?? '');
        $mode = ManagerScopeModeRegistry::AUTO;

        if ($applyScopeMode && $role === RoleRegistry::ROLE_HR_MANAGER) {
            $rawMode = isset($raw['manager_scope_mode'])
                ? strtolower(trim((string) $raw['manager_scope_mode']))
                : ManagerScopeModeRegistry::AUTO;

            $mode = ManagerScopeModeRegistry::isValid($rawMode)
                ? $rawMode
                : ManagerScopeModeRegistry::AUTO;
        }

        return new StaffUserInput(
            role: $role,
            phone: $phone,
            status: (string) ($raw['status'] ?? StaffUser::STATUS_ACTIVE),
            managerScopeMode: $mode,
        );
    }

    private function assertPlatformInput(PlatformUserInput $input, bool $isCreate): void
    {
        if (trim($input->login) === '') {
            throw new ValidationException(esc_html('Username is required.'));
        }

        if (strlen($input->login) > self::LOGIN_MAX) {
            throw new ValidationException(esc_html('Username is too long.'));
        }

        if (trim($input->email) === '') {
            throw new ValidationException(esc_html('Email is required.'));
        }

        if (! filter_var($input->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(esc_html('Email is invalid.'));
        }

        if (trim($input->displayName) === '') {
            throw new ValidationException(esc_html('Display name is required.'));
        }

        if (strlen($input->displayName) > self::DISPLAY_NAME_MAX) {
            throw new ValidationException(esc_html('Display name is too long.'));
        }

        if ($isCreate && ($input->password === null || $input->password === '')) {
            throw new ValidationException(esc_html('Password is required for new users.'));
        }

        if ($input->password !== null && strlen($input->password) < self::PASSWORD_MIN) {
            throw new ValidationException(esc_html('Password must be at least ' . self::PASSWORD_MIN . ' characters.'));
        }
    }

    private function assertStaffInput(StaffUserInput $input): void
    {
        if (! RoleRegistry::isValidRole($input->role)) {
            throw new ValidationException(esc_html('Invalid role.'));
        }

        if (
            ! in_array($input->status, [StaffUser::STATUS_ACTIVE, StaffUser::STATUS_INACTIVE], true)
        ) {
            throw new ValidationException(esc_html('Invalid status.'));
        }

        if ($input->phone !== null && strlen($input->phone) > self::PHONE_MAX) {
            throw new ValidationException(esc_html('Phone must be at most ' . self::PHONE_MAX . ' characters.'));
        }

        if (! ManagerScopeModeRegistry::isValid($input->managerScopeMode)) {
            throw new ValidationException(esc_html('Invalid manager data access scope.'));
        }
    }

    private function normalizeStaffInput(StaffUserInput $input): StaffUserInput
    {
        if ($input->role === RoleRegistry::ROLE_HR_MANAGER) {
            return $input;
        }

        if ($input->managerScopeMode === ManagerScopeModeRegistry::AUTO) {
            return $input;
        }

        return new StaffUserInput(
            role: $input->role,
            phone: $input->phone,
            status: $input->status,
            managerScopeMode: ManagerScopeModeRegistry::AUTO,
        );
    }

    private function enrich(StaffUser $user, ?PlatformUser $platform): StaffUser
    {
        if ($platform === null) {
            return $user;
        }

        return $user->withPlatformData(
            $platform->displayName,
            $platform->email,
            $platform->login,
        );
    }

    private function syncEmployeeProfileForEmployeeRole(
        StaffUserInput $staffInput,
        PlatformUser $platformUser,
        int $actorUserId,
    ): void {
        if ($staffInput->role !== RoleRegistry::ROLE_EMPLOYEE) {
            return;
        }

        if ($this->employees->findIdByPlatformUserId($platformUser->id) !== null) {
            return;
        }

        $emailMatch = $this->employees->findManagerOptionByEmail($platformUser->email);

        if ($emailMatch !== null) {
            $this->employees->linkPlatformUser($emailMatch['id'], $platformUser->id, $actorUserId);

            return;
        }

        $departmentId = $this->employees->firstActiveDepartmentId();

        if ($departmentId === null) {
            throw new ValidationException(esc_html('Cannot create employee profile: create at least one active department first.'));
        }

        $positionId = $this->employees->firstActivePositionIdForDepartment($departmentId)
            ?? $this->employees->firstActivePositionId();

        if ($positionId === null) {
            throw new ValidationException(esc_html('Cannot create employee profile: create at least one active position first.'));
        }

        $this->employees->createForPlatformEmployee(
            $platformUser,
            $departmentId,
            $positionId,
            $actorUserId,
        );
    }

    private function assertEmployeeProvisioningPrerequisites(
        StaffUserInput $staffInput,
        string $email,
    ): void {
        if ($staffInput->role !== RoleRegistry::ROLE_EMPLOYEE) {
            return;
        }

        if ($this->employees->findManagerOptionByEmail($email) !== null) {
            return;
        }

        $departmentId = $this->employees->firstActiveDepartmentId();

        if ($departmentId === null) {
            throw new ValidationException(esc_html('Cannot create employee profile: create at least one active department first.'));
        }

        $positionId = $this->employees->firstActivePositionIdForDepartment($departmentId)
            ?? $this->employees->firstActivePositionId();

        if ($positionId === null) {
            throw new ValidationException(esc_html('Cannot create employee profile: create at least one active position first.'));
        }
    }

    /**
     * @param list<int> $exclude
     * @return PaginatedList<LinkCandidate>
     */
    private function listEmployeeLinkCandidates(LinkCandidateFilter $filter, array $exclude): PaginatedList
    {
        $search = trim($filter->search);
        $result = $this->employees->findUnlinkedWithEmployeePage(
            $exclude,
            $search !== '' ? $search : null,
            max(1, $filter->page),
            max(1, min(50, $filter->perPage)),
        );

        $items = [];

        foreach ($result->items as $row) {
            $candidate = $this->buildLinkCandidateFromEmployeeRow($row);

            if ($candidate !== null) {
                $items[] = $candidate;
            }
        }

        return new PaginatedList($items, $result->total, $result->page, $result->perPage);
    }

    /**
     * @param list<int> $exclude
     * @return PaginatedList<LinkCandidate>
     */
    private function listAllUnlinkedCandidates(LinkCandidateFilter $filter, array $exclude): PaginatedList
    {
        $result = $this->platformUsers->listUnlinkedCandidates(
            trim($filter->search),
            $exclude,
            max(1, $filter->page),
            max(1, min(50, $filter->perPage)),
        );

        $platformIds = array_map(
            static fn (PlatformUser $user): int => $user->id,
            $result->items,
        );
        $employeeMeta = $this->employees->findEmployeeMetaByPlatformUserIds($platformIds);

        $items = array_map(
            fn (PlatformUser $platform): LinkCandidate => $this->buildLinkCandidateFromPlatform(
                $platform,
                $employeeMeta[$platform->id] ?? null,
            ),
            $result->items,
        );

        return new PaginatedList($items, $result->total, $result->page, $result->perPage);
    }

    /**
     * @param array{platform_user_id: int, employee_id: int, department_name: string|null} $row
     */
    private function buildLinkCandidateFromEmployeeRow(array $row): ?LinkCandidate
    {
        $platformUserId = (int) ($row['platform_user_id'] ?? 0);

        if ($platformUserId <= 0) {
            return null;
        }

        $platform = $this->platformUsers->findById($platformUserId);

        if ($platform === null) {
            return null;
        }

        return new LinkCandidate(
            platformUserId: $platformUserId,
            login: $platform->login,
            email: $platform->email,
            displayName: $platform->displayName,
            hasEmployee: true,
            employeeId: (int) ($row['employee_id'] ?? 0) ?: null,
            departmentName: $row['department_name'] ?? null,
            platformRoleLabel: $this->platformUsers->platformRoleLabel($platformUserId),
        );
    }

    /**
     * @param array{employee_id: int, department_name: string|null}|null $employeeMeta
     */
    private function buildLinkCandidateFromPlatform(
        PlatformUser $platform,
        ?array $employeeMeta,
    ): LinkCandidate {
        return new LinkCandidate(
            platformUserId: $platform->id,
            login: $platform->login,
            email: $platform->email,
            displayName: $platform->displayName,
            hasEmployee: $employeeMeta !== null,
            employeeId: $employeeMeta !== null ? ($employeeMeta['employee_id'] ?: null) : null,
            departmentName: $employeeMeta['department_name'] ?? null,
            platformRoleLabel: $this->platformUsers->platformRoleLabel($platform->id),
        );
    }

    private function dispatchProvisioned(StaffUser $staffUser, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::STAFF_USER_PROVISIONED,
                title: 'Your StaffCore account is ready',
                body: 'You have been granted StaffCore access.',
                entityType: 'staff_user',
                entityId: $staffUser->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('settings', 'users')),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_STAFF_USER],
                context: ['staff_user_id' => $staffUser->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchRoleChanged(StaffUser $staffUser, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::STAFF_USER_ROLE_CHANGED,
                title: 'Your StaffCore role changed',
                body: 'Your StaffCore permissions were updated.',
                entityType: 'staff_user',
                entityId: $staffUser->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('settings', 'users')),
                priority: Notification::PRIORITY_HIGH,
                recipientCodes: [StaffCoreEvent::RECIPIENT_STAFF_USER],
                context: ['staff_user_id' => $staffUser->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchDeactivated(StaffUser $staffUser, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::STAFF_USER_DEACTIVATED,
                title: 'Your StaffCore account was deactivated',
                body: 'Contact HR if you believe this is a mistake.',
                entityType: 'staff_user',
                entityId: $staffUser->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('settings', 'users')),
                priority: Notification::PRIORITY_HIGH,
                recipientCodes: [
                    StaffCoreEvent::RECIPIENT_STAFF_USER,
                    StaffCoreEvent::RECIPIENT_ADMIN,
                ],
                context: ['staff_user_id' => $staffUser->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchLoginAttemptsReset(
        StaffUser $staffUser,
        PlatformUser $platform,
        int $actorPlatformUserId,
    ): void {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::STAFF_USER_LOGIN_ATTEMPTS_RESET,
                title: 'Login attempts reset',
                body: sprintf('Login attempts were reset for %s.', $platform->login),
                entityType: 'staff_user',
                entityId: $staffUser->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('settings', 'users')),
                priority: Notification::PRIORITY_LOW,
                recipientCodes: [StaffCoreEvent::RECIPIENT_ADMIN],
                context: ['staff_user_id' => $staffUser->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }
}


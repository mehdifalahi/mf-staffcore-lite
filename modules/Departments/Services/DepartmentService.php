<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Departments\Services;

use MF\StaffCore\Auth\PlatformUserGateway;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Modules\Departments\DTO\Department;
use MF\StaffCore\Modules\Departments\DTO\DepartmentInput;
use MF\StaffCore\Modules\Departments\Repositories\DepartmentRepository;
use MF\StaffCore\Modules\Employees\Repositories\EmployeeRepository;
use MF\StaffCore\Modules\Positions\Repositories\PositionRepository;
use MF\StaffCore\Modules\Users\Repositories\StaffUserRepository;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class DepartmentService
{
    private const NAME_MAX = 255;
    private const CODE_MAX = 50;

    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly StaffUserRepository $staffUsers,
        private readonly PlatformUserGateway $platformUsers,
        private readonly EmployeeRepository $employees,
        private readonly PositionRepository $positions,
    ) {
    }

    /**
     * @return PaginatedList<Department>
     */
    public function listPage(int $page, int $perPage = PaginatedList::DEFAULT_PER_PAGE): PaginatedList
    {
        return $this->repository->findPage($page, $perPage);
    }

    /**
     * @return list<Department>
     */
    public function listActive(): array
    {
        return $this->repository->listActive();
    }

    public function findById(int $id): ?Department
    {
        return $this->repository->findById($id);
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function managerOptions(?int $ensureEmployeeId = null): array
    {
        $platformIds = $this->staffUsers->listActivePlatformUserIdsByRole(RoleRegistry::ROLE_HR_MANAGER);

        if ($platformIds === []) {
            return [];
        }

        $platformUsers = $this->platformUsers->findByIds($platformIds);
        $options = [];
        $seenEmployeeIds = [];

        foreach ($platformIds as $platformId) {
            $option = $this->employees->findManagerOptionByPlatformUserId($platformId);

            if ($option === null) {
                $platformUser = $platformUsers[$platformId] ?? null;

                if ($platformUser !== null) {
                    $option = $this->employees->findManagerOptionByEmail($platformUser->email);
                }
            }

            if ($option !== null) {
                if (! in_array($option['id'], $seenEmployeeIds, true)) {
                    $options[] = $option;
                    $seenEmployeeIds[] = $option['id'];
                }

                continue;
            }

            $platformUser = $platformUsers[$platformId] ?? null;

            if ($platformUser === null) {
                continue;
            }

            $label = trim($platformUser->displayName) !== '' ? $platformUser->displayName : $platformUser->login;

            if ($platformUser->email !== '') {
                $label .= ' (' . $platformUser->email . ')';
            }

            $options[] = [
                'id' => -$platformId,
                'label' => $label,
            ];
        }

        if ($ensureEmployeeId !== null && $ensureEmployeeId > 0) {
            if (! in_array($ensureEmployeeId, $seenEmployeeIds, true)) {
                $extra = $this->employees->findManagerOptionById($ensureEmployeeId);

                if ($extra !== null) {
                    $options[] = $extra;
                }
            }
        }

        usort(
            $options,
            static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']),
        );

        return $options;
    }

    public function create(DepartmentInput $input, int $userId): Department
    {
        $input = $this->normalize($input);
        $this->assertWithoutManager($input);

        if ($this->repository->codeExists($input->code)) {
            throw new ValidationException(esc_html('A department with this code already exists.'));
        }

        $department = $this->repository->create(
            new DepartmentInput(
                name: $input->name,
                code: $input->code,
                description: $input->description,
                managerId: null,
                status: $input->status,
            ),
            $userId,
        );

        $resolvedManagerId = $this->resolveManagerEmployeeId($input->managerId, $department->id, $userId);

        if ($resolvedManagerId === null) {
            return $department;
        }

        $this->assertManager($resolvedManagerId);

        return $this->repository->update(
            $department->id,
            new DepartmentInput(
                name: $input->name,
                code: $input->code,
                description: $input->description,
                managerId: $resolvedManagerId,
                status: $input->status,
            ),
            $userId,
        );
    }

    public function update(int $id, DepartmentInput $input, int $userId): Department
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Department not found.'));
        }

        $input = $this->normalize($input);
        $this->assertWithoutManager($input);

        if ($this->repository->codeExists($input->code, $id)) {
            throw new ValidationException(esc_html('A department with this code already exists.'));
        }

        $resolvedManagerId = $this->resolveManagerEmployeeId($input->managerId, $id, $userId);
        $this->assertManager($resolvedManagerId);

        return $this->repository->update(
            $id,
            new DepartmentInput(
                name: $input->name,
                code: $input->code,
                description: $input->description,
                managerId: $resolvedManagerId,
                status: $input->status,
            ),
            $userId,
        );
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Department not found.'));
        }

        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     */
    public function bulkDelete(array $ids, int $actorUserId): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute($ids, fn (int $id) => $this->delete($id));
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): DepartmentInput
    {
        $managerId = isset($raw['manager_id']) && $raw['manager_id'] !== ''
            ? (int) $raw['manager_id']
            : null;

        return new DepartmentInput(
            name: (string) ($raw['name'] ?? ''),
            code: (string) ($raw['code'] ?? ''),
            description: isset($raw['description']) ? (string) $raw['description'] : null,
            managerId: $managerId,
            status: (string) ($raw['status'] ?? Department::STATUS_ACTIVE),
        );
    }

    private function normalize(DepartmentInput $input): DepartmentInput
    {
        return new DepartmentInput(
            name: trim($input->name),
            code: strtoupper(trim($input->code)),
            description: $this->trimNullable($input->description),
            managerId: $input->managerId,
            status: strtoupper(trim($input->status)),
        );
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assertWithoutManager(DepartmentInput $input): void
    {
        if ($input->name === '') {
            throw new ValidationException(esc_html('Department name is required.'));
        }

        if ($input->code === '') {
            throw new ValidationException(esc_html('Department code is required.'));
        }

        $this->assertMaxLength('Department name', $input->name, self::NAME_MAX);
        $this->assertMaxLength('Department code', $input->code, self::CODE_MAX);

        if (
            ! in_array($input->status, [Department::STATUS_ACTIVE, Department::STATUS_INACTIVE], true)
        ) {
            throw new ValidationException(esc_html('Invalid department status.'));
        }
    }

    private function assertManager(?int $employeeId): void
    {
        if ($employeeId === null) {
            return;
        }

        if (! $this->repository->isEligibleDepartmentManager($employeeId)) {
            throw new ValidationException(esc_html('Department manager must be an active employee linked to a StaffCore user with the Manager role.'));
        }
    }

    private function resolveManagerEmployeeId(?int $managerId, int $departmentId, int $actorUserId): ?int
    {
        if ($managerId === null || $managerId === 0) {
            return null;
        }

        if ($managerId > 0) {
            if ($this->repository->isEligibleDepartmentManager($managerId)) {
                return $managerId;
            }

            $this->tryLinkEmployeeByEmail($managerId, $actorUserId);

            if ($this->repository->isEligibleDepartmentManager($managerId)) {
                return $managerId;
            }

            throw new ValidationException(esc_html('Department manager must be an active employee linked to a StaffCore user with the Manager role.'));
        }

        $platformUserId = abs($managerId);

        if (
            ! $this->staffUsers->isActivePlatformUserWithRole(
                $platformUserId,
                RoleRegistry::ROLE_HR_MANAGER,
            )
        ) {
            throw new ValidationException(esc_html('Invalid manager selection.'));
        }

        $existingEmployeeId = $this->employees->findIdByPlatformUserId($platformUserId);

        if ($existingEmployeeId !== null) {
            return $existingEmployeeId;
        }

        $platformUser = $this->platformUsers->findById($platformUserId);

        if ($platformUser === null) {
            throw new ValidationException(esc_html('Manager user account not found.'));
        }

        $emailOption = $this->employees->findManagerOptionByEmail($platformUser->email);

        if ($emailOption !== null) {
            $this->employees->linkPlatformUser($emailOption['id'], $platformUserId, $actorUserId);

            return $emailOption['id'];
        }

        $positionId = $this->positions->findFirstActiveIdForDepartment($departmentId, 'MANAGER')
            ?? $this->positions->findFirstActiveIdForDepartment($departmentId)
            ?? $this->positions->findFirstActiveId();

        if ($positionId === null) {
            throw new ValidationException(esc_html('Cannot assign manager: create at least one active position in this department first.'));
        }

        return $this->employees->createForPlatformManager(
            $platformUser,
            $departmentId,
            $positionId,
            $actorUserId,
        );
    }

    private function tryLinkEmployeeByEmail(int $employeeId, int $actorUserId): void
    {
        $option = $this->employees->findManagerOptionById($employeeId);

        if ($option === null) {
            return;
        }

        $platformIds = $this->staffUsers->listActivePlatformUserIdsByRole(RoleRegistry::ROLE_HR_MANAGER);
        $platformUsers = $this->platformUsers->findByIds($platformIds);

        foreach ($platformUsers as $platformUser) {
            if (strcasecmp($platformUser->email, $this->employeeEmailById($employeeId) ?? '') !== 0) {
                continue;
            }

            $this->employees->linkPlatformUser($employeeId, $platformUser->id, $actorUserId);

            return;
        }
    }

    private function employeeEmailById(int $employeeId): ?string
    {
        $employee = $this->employees->findById($employeeId);

        return $employee?->email;
    }

    private function assertMaxLength(string $label, ?string $value, int $max): void
    {
        if ($value !== null && strlen($value) > $max) {
            throw new ValidationException( esc_html( $label . ' must be at most ' . $max . ' characters.' ) );
        }
    }
}


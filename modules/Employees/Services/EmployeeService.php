<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\Services;

use MF\StaffCore\Auth\PlatformUserGateway;
use MF\StaffCore\Modules\Departments\Services\DepartmentService;
use MF\StaffCore\Modules\Users\Repositories\StaffUserRepository;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\Employees\DTO\EmployeeAllowance;
use MF\StaffCore\Modules\Employees\DTO\EmployeeInput;
use MF\StaffCore\Modules\Employees\DTO\EmployeeListFilter;
use MF\StaffCore\Modules\Employees\Repositories\EmployeeAllowanceRepository;
use MF\StaffCore\Modules\Employees\Repositories\EmployeeRepository;
use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Navigation\PanelActionUrl;
use MF\StaffCore\Notification\EventCatalog;
use MF\StaffCore\Notification\Notification;
use MF\StaffCore\Notification\NotificationDispatcher;
use MF\StaffCore\Notification\StaffCoreEvent;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class EmployeeService
{
    private const CODE_MAX = 50;
    private const FIRST_NAME_MAX = 100;
    private const LAST_NAME_MAX = 100;
    private const EMAIL_MAX = 255;
    private const PHONE_MAX = 20;
    private const CITY_MAX = 100;
    private const PROVINCE_MAX = 100;
    private const COUNTRY_MAX = 100;
    private const ZIP_MAX = 20;
    private const PHOTO_MAX = 500;
    private const ALLOWANCE_NAME_MAX = 255;

    public function __construct(
        private readonly EmployeeRepository $repository,
        private readonly EmployeeAllowanceRepository $allowanceRepository,
        private readonly DepartmentService $departmentService,
        private readonly NotificationDispatcher $notificationDispatcher,
        private readonly PlatformUserGateway $platformUsers,
        private readonly StaffUserRepository $staffUserRepository,
    ) {
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     * @return PaginatedList<Employee>
     */
    public function listPage(
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?array $scopeEmployeeIds = null,
        ?EmployeeListFilter $filter = null,
    ): PaginatedList {
        return $this->repository->findPage($page, $perPage, $scopeEmployeeIds, $filter);
    }

    public function countAll(): int
    {
        return $this->repository->countAll();
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function findById(int $id, ?array $scopeEmployeeIds = null): ?Employee
    {
        return $this->repository->findById($id, $scopeEmployeeIds);
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function get(int $id, ?array $scopeEmployeeIds = null): ?Employee
    {
        $employee = $this->repository->findById($id, $scopeEmployeeIds);

        if ($employee === null) {
            return null;
        }

        return $this->withAllowances($employee);
    }

    public function create(EmployeeInput $input, int $userId): Employee
    {
        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->codeExists($input->code)) {
            throw new ValidationException(esc_html('An employee with this code already exists.'));
        }

        if ($this->repository->emailExists($input->email)) {
            throw new ValidationException(esc_html('An employee with this email already exists.'));
        }

        if ($input->userId !== null && $this->repository->userIdExists($input->userId)) {
            throw new ValidationException(esc_html('This user account is already linked to another employee.'));
        }

        

        $employee = $this->repository->create($input, $userId);
        $this->allowanceRepository->syncForEmployee($employee->id, $input->allowances, $userId);
        $employee = $this->withAllowances($employee);
        $this->dispatchCreated($employee, $userId);

        return $employee;
    }

    public function update(int $id, EmployeeInput $input, int $userId, ?array $scopeEmployeeIds = null): Employee
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Employee not found.'));
        }

        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->codeExists($input->code, $id)) {
            throw new ValidationException(esc_html('An employee with this code already exists.'));
        }

        if ($this->repository->emailExists($input->email, $id)) {
            throw new ValidationException(esc_html('An employee with this email already exists.'));
        }

        if (
            $input->userId !== null
            && $this->repository->userIdExists($input->userId, $id)
        ) {
            throw new ValidationException(esc_html('This user account is already linked to another employee.'));
        }

        

        $employee = $this->repository->update($id, $input, $userId);
        $this->allowanceRepository->syncForEmployee($id, $input->allowances, $userId);
        $employee = $this->withAllowances($employee);

        if ($existing->employmentStatus !== $employee->employmentStatus) {
            if (in_array($employee->employmentStatus, [Employee::STATUS_RESIGNED, Employee::STATUS_TERMINATED], true)) {
                $this->dispatchStatusTerminated($employee, $userId);
            } else {
                $this->dispatchStatusChanged($employee, $userId);
            }
        }

        $wasUnlinked = $existing->userId === null || $existing->userId <= 0;
        $isLinked = $employee->userId !== null && $employee->userId > 0;

        if ($wasUnlinked && $isLinked) {
            $this->dispatchUserLinked($employee, $userId);
        }

        return $employee;
    }

    public function delete(int $id, ?array $scopeEmployeeIds = null): void
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Employee not found.'));
        }

        $this->deleteStoredPhotoFile($existing->photo);
        $this->allowanceRepository->deleteByEmployeeId($id);
        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     */
    public function bulkDelete(array $ids, int $actorUserId, ?array $scopeEmployeeIds = null): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute(
            $ids,
            fn (int $id) => $this->delete($id, $scopeEmployeeIds),
        );
    }

    public function removePhoto(int $id, int $userId, ?array $scopeEmployeeIds = null): Employee
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Employee not found.'));
        }

        $this->deleteStoredPhotoFile($existing->photo);

        return $this->withAllowances($this->repository->clearPhoto($id, $userId));
    }

    /**
     * @param list<int>|null $scopeEmployeeIds
     */
    public function replacePhoto(int $id, string $photoUrl, int $userId, ?array $scopeEmployeeIds = null): Employee
    {
        $existing = $this->repository->findById($id, $scopeEmployeeIds);

        if ($existing === null) {
            throw new ValidationException(esc_html('Employee not found.'));
        }

        $photoUrl = trim($photoUrl);

        if ($photoUrl === '') {
            throw new ValidationException(esc_html('Photo is required.'));
        }

        $this->assertMaxLength('Photo', $photoUrl, self::PHOTO_MAX);

        if ($existing->photo !== null && $existing->photo !== '' && $existing->photo !== $photoUrl) {
            $this->deleteStoredPhotoFile($existing->photo);
        }

        return $this->withAllowances($this->repository->updatePhoto($id, $photoUrl, $userId));
    }

    public function findByPlatformUserId(int $platformUserId): ?Employee
    {
        $employeeId = $this->repository->findIdByPlatformUserId($platformUserId);

        if ($employeeId === null || $employeeId <= 0) {
            return null;
        }

        return $this->repository->findById($employeeId, [$employeeId]);
    }

    private function deleteStoredPhotoFile(?string $photoUrl): void
    {
        if ($photoUrl === null || $photoUrl === '') {
            return;
        }

        if (! function_exists('wp_upload_dir')) {
            return;
        }

        $uploadDir = wp_upload_dir();
        $baseUrl = $uploadDir['baseurl'] ?? '';

        if ($baseUrl === '' || ! str_starts_with($photoUrl, $baseUrl)) {
            return;
        }

        $relative = ltrim(substr($photoUrl, strlen($baseUrl)), '/');
        $filePath = wp_normalize_path($uploadDir['basedir'] . '/' . $relative);

        if ($filePath !== '' && is_file($filePath) && function_exists('wp_delete_file')) {
            wp_delete_file($filePath);
        }
    }

    /**
     * @return list<int>
     */
    public function linkedUserIds(?int $excludeEmployeeId = null): array
    {
        return $this->repository->findLinkedUserIds($excludeEmployeeId);
    }

    /**
     * Active StaffCore accounts not already linked to another employee.
     *
     * @return list<array{id: int, label: string}>
     */
    public function listLinkableUserAccountOptions(?int $excludeEmployeeId = null): array
    {
        $candidateIds = $this->staffUserRepository->listUnlinkedActivePlatformUserIds($excludeEmployeeId);

        if ($candidateIds === []) {
            return [];
        }

        $platformMap = $this->platformUsers->findByIds($candidateIds);
        $options = [];

        foreach ($candidateIds as $platformUserId) {
            $platformUser = $platformMap[$platformUserId] ?? null;

            if ($platformUser === null) {
                continue;
            }

            $options[] = [
                'id' => $platformUser->id,
                'label' => $platformUser->accountLabel(),
            ];
        }

        usort(
            $options,
            static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']),
        );

        return $options;
    }

    public function resolvePlatformUserAccountLabel(int $platformUserId): ?string
    {
        if ($platformUserId <= 0) {
            return null;
        }

        $platformUser = $this->platformUsers->findById($platformUserId);

        return $platformUser?->accountLabel();
    }

    /**
     * @param list<int> $platformUserIds
     * @return array<int, string>
     */
    public function photosByPlatformUserIds(array $platformUserIds): array
    {
        return $this->repository->findPhotosByPlatformUserIds($platformUserIds);
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): EmployeeInput
    {
        $linkedUserId = isset($raw['user_id']) && $raw['user_id'] !== ''
            ? (int) $raw['user_id']
            : null;

        $basicSalary = isset($raw['basic_salary']) && $raw['basic_salary'] !== ''
            ? (float) $raw['basic_salary']
            : null;

        $annualLeave = isset($raw['annual_leave']) && $raw['annual_leave'] !== ''
            ? (int) $raw['annual_leave']
            : 0;

        return new EmployeeInput(
            code: (string) ($raw['code'] ?? ''),
            departmentId: (int) ($raw['department_id'] ?? 0),
            positionId: (int) ($raw['position_id'] ?? 0),
            firstName: (string) ($raw['first_name'] ?? ''),
            lastName: (string) ($raw['last_name'] ?? ''),
            email: (string) ($raw['email'] ?? ''),
            phone: isset($raw['phone']) ? (string) $raw['phone'] : null,
            userId: $linkedUserId,
            birthDate: isset($raw['birth_date']) ? (string) $raw['birth_date'] : null,
            hireDate: (string) ($raw['hire_date'] ?? ''),
            employmentStatus: (string) ($raw['employment_status'] ?? Employee::STATUS_ACTIVE),
            employmentType: (string) ($raw['employment_type'] ?? Employee::TYPE_FULLTIME),
            basicSalary: $basicSalary,
            annualLeave: $annualLeave,
            address: isset($raw['address']) ? (string) $raw['address'] : null,
            city: isset($raw['city']) ? (string) $raw['city'] : null,
            province: isset($raw['province']) ? (string) $raw['province'] : null,
            country: isset($raw['country']) ? (string) $raw['country'] : null,
            zipCode: isset($raw['zip_code']) ? (string) $raw['zip_code'] : null,
            photo: isset($raw['photo']) ? (string) $raw['photo'] : null,
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
            allowances: $this->parseAllowances($raw['allowances'] ?? []),
        );
    }

    /**
     * @param mixed $raw
     * @return list<array{name: string, amount: float}>
     */
    private function parseAllowances(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [];
            }

            if (! is_array($decoded)) {
                return [];
            }

            $raw = $decoded;
        }

        if (! is_array($raw)) {
            return [];
        }

        $allowances = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = sanitize_text_field(trim((string) ($item['name'] ?? '')));

            if ($name === '') {
                continue;
            }

            $amount = (float) ($item['amount'] ?? 0);

            if ($amount < 0) {
                continue;
            }

            $allowances[] = [
                'name' => $name,
                'amount' => $amount,
            ];
        }

        return $allowances;
    }

    private function withAllowances(Employee $employee): Employee
    {
        $allowances = $this->allowanceRepository->findByEmployeeId($employee->id);

        return new Employee(
            id: $employee->id,
            code: $employee->code,
            departmentId: $employee->departmentId,
            positionId: $employee->positionId,
            firstName: $employee->firstName,
            lastName: $employee->lastName,
            email: $employee->email,
            phone: $employee->phone,
            userId: $employee->userId,
            birthDate: $employee->birthDate,
            hireDate: $employee->hireDate,
            employmentStatus: $employee->employmentStatus,
            employmentType: $employee->employmentType,
            basicSalary: $employee->basicSalary,
            annualLeave: $employee->annualLeave,
            address: $employee->address,
            city: $employee->city,
            province: $employee->province,
            country: $employee->country,
            zipCode: $employee->zipCode,
            photo: $employee->photo,
            notes: $employee->notes,
            departmentName: $employee->departmentName,
            positionTitle: $employee->positionTitle,
            positionLevel: $employee->positionLevel,
            remainingLeave: $employee->remainingLeave,
            allowances: $allowances,
        );
    }

    private function normalize(EmployeeInput $input): EmployeeInput
    {
        $employmentType = trim($input->employmentType);

        if ($employmentType === '') {
            $employmentType = Employee::TYPE_FULLTIME;
        }

        return new EmployeeInput(
            code: strtoupper(trim($input->code)),
            departmentId: $input->departmentId,
            positionId: $input->positionId,
            firstName: trim($input->firstName),
            lastName: trim($input->lastName),
            email: strtolower(trim($input->email)),
            phone: $this->trimNullable($input->phone),
            userId: $input->userId,
            birthDate: $this->normalizeDate($input->birthDate),
            hireDate: (string) ($this->normalizeDate($input->hireDate) ?? ''),
            employmentStatus: strtoupper(trim($input->employmentStatus)),
            employmentType: strtoupper($employmentType),
            basicSalary: $input->basicSalary,
            annualLeave: max(0, $input->annualLeave),
            address: $this->trimNullable($input->address),
            city: $this->trimNullable($input->city),
            province: $this->trimNullable($input->province),
            country: $this->trimNullable($input->country),
            zipCode: $this->trimNullable($input->zipCode),
            photo: $this->trimNullable($input->photo),
            notes: $this->trimNullable($input->notes),
            allowances: $input->allowances,
        );
    }

    private function normalizeDate(?string $value): ?string
    {
        return $this->trimNullable($value);
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assert(EmployeeInput $input): void
    {
        if ($input->code === '') {
            throw new ValidationException(esc_html('Employee code is required.'));
        }

        if ($input->departmentId <= 0) {
            throw new ValidationException(esc_html('Department is required.'));
        }

        if ($input->positionId <= 0) {
            throw new ValidationException(esc_html('Position is required.'));
        }

        if ($input->firstName === '') {
            throw new ValidationException(esc_html('First name is required.'));
        }

        if ($input->lastName === '') {
            throw new ValidationException(esc_html('Last name is required.'));
        }

        if ($input->email === '') {
            throw new ValidationException(esc_html('Email is required.'));
        }

        if ($input->hireDate === '') {
            throw new ValidationException(esc_html('Hire date is required.'));
        }

        if (! filter_var($input->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(esc_html('Please enter a valid email address.'));
        }

        $this->assertMaxLength('Employee code', $input->code, self::CODE_MAX);
        $this->assertMaxLength('First name', $input->firstName, self::FIRST_NAME_MAX);
        $this->assertMaxLength('Last name', $input->lastName, self::LAST_NAME_MAX);
        $this->assertMaxLength('Email', $input->email, self::EMAIL_MAX);
        $this->assertMaxLength('Phone', $input->phone, self::PHONE_MAX);
        $this->assertMaxLength('City', $input->city, self::CITY_MAX);
        $this->assertMaxLength('Province', $input->province, self::PROVINCE_MAX);
        $this->assertMaxLength('Country', $input->country, self::COUNTRY_MAX);
        $this->assertMaxLength('Zip code', $input->zipCode, self::ZIP_MAX);
        $this->assertMaxLength('Photo', $input->photo, self::PHOTO_MAX);

        $statuses = [
            Employee::STATUS_ACTIVE,
            Employee::STATUS_ON_LEAVE,
            Employee::STATUS_SUSPENDED,
            Employee::STATUS_RESIGNED,
            Employee::STATUS_TERMINATED,
        ];

        if (! in_array($input->employmentStatus, $statuses, true)) {
            throw new ValidationException(esc_html('Invalid employment status.'));
        }

        $types = [
            Employee::TYPE_FULLTIME,
            Employee::TYPE_PARTTIME,
            Employee::TYPE_CONTRACT,
            Employee::TYPE_TEMPORARY,
            Employee::TYPE_INTERN,
            Employee::TYPE_FREELANCE,
        ];

        if (! in_array($input->employmentType, $types, true)) {
            throw new ValidationException(esc_html('Invalid employment type.'));
        }

        if ($this->departmentService->findById($input->departmentId) === null) {
            throw new ValidationException(esc_html('Selected department does not exist.'));
        }

        if (
            ! $this->repository->positionBelongsToDepartment(
                $input->positionId,
                $input->departmentId,
            )
        ) {
            throw new ValidationException(esc_html('Selected position does not belong to the department.'));
        }

        foreach ($input->allowances as $allowance) {
            $this->assertMaxLength('Allowance name', $allowance['name'], self::ALLOWANCE_NAME_MAX);
        }
    }

    private function assertMaxLength(string $label, ?string $value, int $max): void
    {
        if ($value !== null && strlen($value) > $max) {
            throw new ValidationException( esc_html( $label . ' must not exceed ' . $max . ' characters.' ) );
        }
    }

    private function employeeLabel(Employee $employee): string
    {
        $name = trim($employee->firstName . ' ' . $employee->lastName);

        return $name !== '' ? $name : ('Employee #' . $employee->id);
    }

    private function dispatchCreated(Employee $employee, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::EMPLOYEE_CREATED,
                title: 'New employee added',
                body: sprintf('%s (%s) was added to the directory.', $this->employeeLabel($employee), $employee->code),
                entityType: 'employee',
                entityId: $employee->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'employees')),
                priority: Notification::PRIORITY_LOW,
                recipientCodes: [StaffCoreEvent::RECIPIENT_HR],
                context: ['employee_id' => $employee->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchStatusChanged(Employee $employee, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::EMPLOYEE_STATUS_CHANGED,
                title: 'Employment status updated',
                body: sprintf(
                    '%s status is now %s.',
                    $this->employeeLabel($employee),
                    $employee->employmentStatus,
                ),
                entityType: 'employee',
                entityId: $employee->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'employees')),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [
                    StaffCoreEvent::RECIPIENT_EMPLOYEE,
                    StaffCoreEvent::RECIPIENT_HR,
                ],
                context: ['employee_id' => $employee->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchStatusTerminated(Employee $employee, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::EMPLOYEE_STATUS_TERMINATED,
                title: 'Employment ended',
                body: sprintf(
                    '%s employment status is %s.',
                    $this->employeeLabel($employee),
                    $employee->employmentStatus,
                ),
                entityType: 'employee',
                entityId: $employee->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'employees')),
                priority: Notification::PRIORITY_HIGH,
                recipientCodes: [
                    StaffCoreEvent::RECIPIENT_EMPLOYEE,
                    StaffCoreEvent::RECIPIENT_HR,
                    StaffCoreEvent::RECIPIENT_ADMIN,
                ],
                context: ['employee_id' => $employee->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }

    private function dispatchUserLinked(Employee $employee, int $actorPlatformUserId): void
    {
        $this->notificationDispatcher->dispatch(
            new StaffCoreEvent(
                eventKey: EventCatalog::EMPLOYEE_USER_LINKED,
                title: 'StaffCore access linked',
                body: sprintf(
                    'A user account was linked to %s. You can sign in to StaffCore.',
                    $this->employeeLabel($employee),
                ),
                entityType: 'employee',
                entityId: $employee->id,
                actionUrl: PanelActionUrl::relative(new AdminRoute('hr-management', 'employees')),
                priority: Notification::PRIORITY_NORMAL,
                recipientCodes: [StaffCoreEvent::RECIPIENT_EMPLOYEE],
                context: ['employee_id' => $employee->id],
                actorPlatformUserId: $actorPlatformUserId,
            ),
        );
    }
}


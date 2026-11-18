<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Positions\Services;

use MF\StaffCore\Modules\Departments\Services\DepartmentService;
use MF\StaffCore\Modules\Positions\DTO\Position;
use MF\StaffCore\Modules\Positions\DTO\PositionInput;
use MF\StaffCore\Modules\Positions\Repositories\PositionRepository;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class PositionService
{
    private const TITLE_MAX = 255;
    private const CODE_MAX = 50;

    public function __construct(
        private readonly PositionRepository $repository,
        private readonly DepartmentService $departmentService,
    ) {
    }

    /**
     * @return PaginatedList<Position>
     */
    public function listPage(int $page, int $perPage = PaginatedList::DEFAULT_PER_PAGE): PaginatedList
    {
        return $this->repository->findPage($page, $perPage);
    }

    /**
     * @return list<Position>
     */
    public function listActive(): array
    {
        return $this->repository->listActive();
    }

    /**
     * @return list<Position>
     */
    public function listActiveByDepartment(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        return $this->repository->listActiveByDepartment($departmentId);
    }

    public function create(PositionInput $input, int $userId): Position
    {
        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->codeExists($input->code)) {
            throw new ValidationException(esc_html('A position with this code already exists.'));
        }

        return $this->repository->create($input, $userId);
    }

    public function update(int $id, PositionInput $input, int $userId): Position
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Position not found.'));
        }

        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->codeExists($input->code, $id)) {
            throw new ValidationException(esc_html('A position with this code already exists.'));
        }

        return $this->repository->update($id, $input, $userId);
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Position not found.'));
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
    public function inputFromArray(array $raw): PositionInput
    {
        return new PositionInput(
            title: (string) ($raw['title'] ?? ''),
            code: (string) ($raw['code'] ?? ''),
            departmentId: (int) ($raw['department_id'] ?? 0),
            level: (string) ($raw['level'] ?? Position::LEVEL_MID),
            minSalary: isset($raw['min_salary']) ? (string) $raw['min_salary'] : null,
            maxSalary: isset($raw['max_salary']) ? (string) $raw['max_salary'] : null,
            description: isset($raw['description']) ? (string) $raw['description'] : null,
            status: (string) ($raw['status'] ?? Position::STATUS_ACTIVE),
        );
    }

    private function normalize(PositionInput $input): PositionInput
    {
        return new PositionInput(
            title: trim($input->title),
            code: strtoupper(trim($input->code)),
            departmentId: $input->departmentId,
            level: strtoupper(trim($input->level)),
            minSalary: $this->normalizeDecimal($input->minSalary),
            maxSalary: $this->normalizeDecimal($input->maxSalary),
            description: $this->trimNullable($input->description),
            status: strtoupper(trim($input->status)),
        );
    }

    private function normalizeDecimal(?string $value): ?string
    {
        $value = $this->trimNullable($value);

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            return $value;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assert(PositionInput $input): void
    {
        if ($input->title === '') {
            throw new ValidationException(esc_html('Position title is required.'));
        }

        if ($input->code === '') {
            throw new ValidationException(esc_html('Position code is required.'));
        }

        if ($input->departmentId <= 0) {
            throw new ValidationException(esc_html('Department is required.'));
        }

        $this->assertMaxLength('Position title', $input->title, self::TITLE_MAX);
        $this->assertMaxLength('Position code', $input->code, self::CODE_MAX);

        if ($this->departmentService->findById($input->departmentId) === null) {
            throw new ValidationException(esc_html('Selected department does not exist.'));
        }

        if (
            ! in_array($input->level, [
                Position::LEVEL_ENTRY,
                Position::LEVEL_JUNIOR,
                Position::LEVEL_MID,
                Position::LEVEL_SENIOR,
                Position::LEVEL_LEAD,
                Position::LEVEL_MANAGER,
                Position::LEVEL_DIRECTOR,
                Position::LEVEL_EXECUTIVE,
            ], true)
        ) {
            throw new ValidationException(esc_html('Invalid position level.'));
        }

        if (
            ! in_array($input->status, [Position::STATUS_ACTIVE, Position::STATUS_INACTIVE], true)
        ) {
            throw new ValidationException(esc_html('Invalid position status.'));
        }

        if ($input->minSalary !== null && ! is_numeric($input->minSalary)) {
            throw new ValidationException(esc_html('Minimum salary must be a number.'));
        }

        if ($input->maxSalary !== null && ! is_numeric($input->maxSalary)) {
            throw new ValidationException(esc_html('Maximum salary must be a number.'));
        }

        if (
            $input->minSalary !== null
            && $input->maxSalary !== null
            && (float) $input->minSalary > (float) $input->maxSalary
        ) {
            throw new ValidationException(esc_html('Minimum salary cannot be greater than maximum salary.'));
        }
    }

    private function assertMaxLength(string $label, ?string $value, int $max): void
    {
        if ($value !== null && strlen($value) > $max) {
            throw new ValidationException( esc_html( $label . ' must be at most ' . $max . ' characters.' ) );
        }
    }
}


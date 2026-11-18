<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyPayslips\Services;

use MF\StaffCore\Modules\Employees\Repositories\EmployeeRepository;
use MF\StaffCore\Modules\Payroll\DTO\Payroll;
use MF\StaffCore\Modules\Payroll\DTO\PayrollListFilter;
use MF\StaffCore\Modules\Payroll\Repositories\PayrollRepository;
use MF\StaffCore\Modules\MyPayslips\DTO\EmployeePayslipSummary;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class MyPayslipsService
{
    public function __construct(
        private readonly PayrollRepository $payrollRepository,
        private readonly EmployeeRepository $employeeRepository,
    ) {
    }

    public function linkedEmployeeId(int $platformUserId): ?int
    {
        return $this->employeeRepository->findIdByPlatformUserId($platformUserId);
    }

    /**
     * @return PaginatedList<Payroll>
     */
    public function listPageForEmployee(
        int $employeeId,
        int $page,
        int $perPage = PaginatedList::DEFAULT_PER_PAGE,
        ?PayrollListFilter $filter = null,
    ): PaginatedList {
        if ($employeeId <= 0) {
            throw new ValidationException(esc_html('Employee is required.'));
        }

        return $this->payrollRepository->findPage(
            $page,
            $perPage,
            [$employeeId],
            $filter,
        );
    }

    public function findPayslipForEmployee(int $payrollId, int $employeeId): ?Payroll
    {
        if ($payrollId <= 0 || $employeeId <= 0) {
            return null;
        }

        $payroll = $this->payrollRepository->findById($payrollId);

        if ($payroll === null || $payroll->employeeId !== $employeeId) {
            return null;
        }

        return $payroll;
    }

    public function employeeSummary(int $employeeId, int $currentYear): EmployeePayslipSummary
    {
        return $this->payrollRepository->employeePayslipSummary($employeeId, $currentYear);
    }

    /**
     * @return list<int>
     */
    public function distinctYearsForEmployee(int $employeeId): array
    {
        return $this->payrollRepository->distinctYearsForEmployee($employeeId);
    }
}


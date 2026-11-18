<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Employees\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\Employees\DTO\EmployeeAllowance;
use RuntimeException;

final class EmployeeAllowanceRepository
{
    private const TABLE = 'employee_allowance';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    /**
     * @return list<EmployeeAllowance>
     */
    public function findByEmployeeId(int $employeeId): array
    {
        $table = $this->db->resolveTableName(self::TABLE);
        $rows = $this->db->getResults(
            'SELECT id, employee_id, name, amount
             FROM `' . $table . '`
             WHERE employee_id = %d
             ORDER BY id ASC',
            [$employeeId],
        );

        return array_map(
            static fn (array $row): EmployeeAllowance => EmployeeAllowance::fromRow($row),
            $rows,
        );
    }

    public function deleteByEmployeeId(int $employeeId): void
    {
        $this->db->delete(self::TABLE, ['employee_id' => $employeeId]);
    }

    /**
     * @param list<array{name: string, amount: float}> $allowances
     */
    public function syncForEmployee(int $employeeId, array $allowances, int $userId): void
    {
        $this->deleteByEmployeeId($employeeId);

        if ($allowances === []) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');

        foreach ($allowances as $allowance) {
            $id = $this->idSequence->nextId(self::TABLE);
            $ok = $this->db->insert(self::TABLE, [
                'id' => $id,
                'employee_id' => $employeeId,
                'name' => $allowance['name'],
                'amount' => number_format($allowance['amount'], 2, '.', ''),
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if (! $ok) {
                throw new RuntimeException(esc_html('Failed to save employee allowance: ' . $this->db->getLastError()));
            }
        }
    }
}


<?php

declare(strict_types=1);

namespace MF\StaffCore\Notification;

use MF\StaffCore\Database\TableGateway;

final class RecipientResolver
{
    public function __construct(
        private readonly TableGateway $db,
    ) {
    }

    /**
     * @param list<string> $recipientCodes
     * @param array<string, mixed> $context
     * @return list<int>
     */
    public function resolveRecipientStaffUserIds(
        array $recipientCodes,
        array $context,
        ?int $actorPlatformUserId = null,
        bool $includeActor = false,
    ): array {
        $resolved = [];

        foreach ($recipientCodes as $code) {
            $resolved = array_merge($resolved, match ($code) {
                StaffCoreEvent::RECIPIENT_EMPLOYEE => $this->resolveEmployeeRecipient($context),
                StaffCoreEvent::RECIPIENT_MANAGER => $this->resolveManagerRecipient($context),
                StaffCoreEvent::RECIPIENT_HR => $this->resolveRoleRecipients('hr_manager'),
                StaffCoreEvent::RECIPIENT_ADMIN => $this->resolveRoleRecipients('administrator'),
                StaffCoreEvent::RECIPIENT_ACTOR => $this->resolveActorRecipient($actorPlatformUserId),
                StaffCoreEvent::RECIPIENT_STAFF_USER => $this->resolveDirectStaffUserRecipient($context),
                default => [],
            });
        }

        $resolved = array_values(array_unique(array_filter($resolved, static fn (int $id): bool => $id > 0)));

        if ($includeActor) {
            return $resolved;
        }

        $actorStaffUserId = $this->findStaffUserIdByPlatformUserId($actorPlatformUserId);
        if ($actorStaffUserId === null) {
            return $resolved;
        }

        return array_values(array_filter(
            $resolved,
            static fn (int $id): bool => $id !== $actorStaffUserId,
        ));
    }

    public function findStaffUserIdByPlatformUserId(?int $platformUserId): ?int
    {
        if ($platformUserId === null || $platformUserId <= 0) {
            return null;
        }

        $table = $this->db->resolveTableName('staff_user');
        $value = $this->db->getVar(
            'SELECT id FROM `' . $table . '` WHERE platform_user_id = %d AND status = %s LIMIT 1',
            [$platformUserId, 'active'],
        );

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $context
     * @return list<int>
     */
    private function resolveEmployeeRecipient(array $context): array
    {
        $employeeId = isset($context['employee_id']) ? (int) $context['employee_id'] : 0;
        if ($employeeId <= 0) {
            return [];
        }

        $employeeTable = $this->db->resolveTableName('employee');
        $staffTable = $this->db->resolveTableName('staff_user');

        $value = $this->db->getVar(
            'SELECT su.id
             FROM `' . $employeeTable . '` AS e
             INNER JOIN `' . $staffTable . '` AS su ON su.platform_user_id = e.user_id
             WHERE e.id = %d AND su.status = %s
             LIMIT 1',
            [$employeeId, 'active'],
        );

        if ($value === null || $value === '') {
            return [];
        }

        return [(int) $value];
    }

    /**
     * @param array<string, mixed> $context
     * @return list<int>
     */
    private function resolveManagerRecipient(array $context): array
    {
        $employeeId = isset($context['employee_id']) ? (int) $context['employee_id'] : 0;
        if ($employeeId <= 0) {
            return [];
        }

        $employeeTable = $this->db->resolveTableName('employee');
        $departmentTable = $this->db->resolveTableName('department');
        $staffTable = $this->db->resolveTableName('staff_user');

        $value = $this->db->getVar(
            'SELECT su.id
             FROM `' . $employeeTable . '` AS subject_employee
             INNER JOIN `' . $departmentTable . '` AS d ON d.id = subject_employee.department_id
             INNER JOIN `' . $employeeTable . '` AS manager_employee ON manager_employee.id = d.manager_id
             INNER JOIN `' . $staffTable . '` AS su ON su.platform_user_id = manager_employee.user_id
             WHERE subject_employee.id = %d AND su.status = %s
             LIMIT 1',
            [$employeeId, 'active'],
        );

        if ($value === null || $value === '') {
            return [];
        }

        return [(int) $value];
    }

    /**
     * @return list<int>
     */
    private function resolveRoleRecipients(string $role): array
    {
        $table = $this->db->resolveTableName('staff_user');
        $rows = $this->db->getResults(
            'SELECT id
             FROM `' . $table . '`
             WHERE role = %s AND status = %s
             ORDER BY id DESC',
            [$role, 'active'],
        );

        return array_values(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows,
        ));
    }

    /**
     * @return list<int>
     */
    private function resolveActorRecipient(?int $actorPlatformUserId): array
    {
        $actor = $this->findStaffUserIdByPlatformUserId($actorPlatformUserId);

        if ($actor === null) {
            return [];
        }

        return [$actor];
    }

    /**
     * @param array<string, mixed> $context
     * @return list<int>
     */
    private function resolveDirectStaffUserRecipient(array $context): array
    {
        $staffUserId = isset($context['staff_user_id']) ? (int) $context['staff_user_id'] : 0;
        if ($staffUserId <= 0) {
            return [];
        }

        return [$staffUserId];
    }
}

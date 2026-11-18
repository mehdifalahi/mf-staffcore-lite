<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\MyProfile\Services;

use MF\StaffCore\Auth\PlatformUserGateway;
use MF\StaffCore\Modules\Employees\DTO\Employee;
use MF\StaffCore\Modules\Employees\Services\EmployeeService;
use MF\StaffCore\Support\ValidationException;

final class MyProfileService
{
    public const PASSWORD_MIN = 8;

    public function __construct(
        private readonly EmployeeService $employees,
        private readonly PlatformUserGateway $platformUsers,
    ) {
    }

    public function linkedProfile(int $platformUserId): ?Employee
    {
        return $this->employees->findByPlatformUserId($platformUserId);
    }

    public function updateProfilePhoto(int $platformUserId, string $photoUrl, int $actorUserId): Employee
    {
        $employee = $this->linkedProfile($platformUserId);

        if ($employee === null) {
            throw new ValidationException(esc_html('No employee record is linked to your account. Contact HR to complete your profile.'));
        }

        return $this->employees->replacePhoto(
            $employee->id,
            $photoUrl,
            $actorUserId,
            [$employee->id],
        );
    }

    public function changeOwnPassword(
        int $platformUserId,
        string $currentPassword,
        string $newPassword,
        string $newPasswordConfirm,
    ): void {
        $currentPassword = (string) $currentPassword;

        if ($currentPassword === '') {
            throw new ValidationException(esc_html('Current password is required.'));
        }

        if (! $this->platformUsers->verifyPassword($platformUserId, $currentPassword)) {
            throw new ValidationException(esc_html('Current password is incorrect.'));
        }

        $newPassword = trim($newPassword);

        if ($newPassword === '') {
            throw new ValidationException(esc_html('New password is required.'));
        }

        if (strlen($newPassword) < self::PASSWORD_MIN) {
            throw new ValidationException(esc_html('Password must be at least ' . self::PASSWORD_MIN . ' characters.'));
        }

        if ($newPassword !== trim($newPasswordConfirm)) {
            throw new ValidationException(esc_html('Password confirmation does not match.'));
        }

        if (hash_equals($currentPassword, $newPassword)) {
            throw new ValidationException(esc_html('New password must be different from your current password.'));
        }

        $this->platformUsers->setPassword($platformUserId, $newPassword);
    }
}


<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

use MF\StaffCore\Modules\Users\DTO\StaffUser;
use MF\StaffCore\Modules\Users\DTO\StaffUserInput;
use MF\StaffCore\Modules\Users\Repositories\StaffUserRepository;
use RuntimeException;

/**
 * Idempotent install bootstrap for the primary StaffCore administrator account.
 */
final class StaffUserBootstrapSeeder
{
    public const DEFAULT_ADMIN_PLATFORM_USER_ID = 1;

    public function __construct(
        private readonly StaffUserRepository $staffUsers,
    ) {
    }

    public function ensureAdministratorSeed(
        int $platformUserId = self::DEFAULT_ADMIN_PLATFORM_USER_ID,
    ): void {
        if ($platformUserId <= 0) {
            return;
        }

        try {
            if ($this->staffUsers->findByPlatformUserId($platformUserId) !== null) {
                return;
            }

            $this->staffUsers->create(
                $platformUserId,
                new StaffUserInput(
                    role: RoleRegistry::ROLE_ADMINISTRATOR,
                    phone: null,
                    status: StaffUser::STATUS_ACTIVE,
                ),
                $platformUserId,
            );
        } catch (RuntimeException) {
            // Schema may not be ready yet; safe to retry on next request or activation.
        }
    }
}

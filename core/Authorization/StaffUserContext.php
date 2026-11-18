<?php



declare(strict_types=1);



namespace MF\StaffCore\Authorization;



use MF\StaffCore\Modules\Users\DTO\StaffUser;



/**

 * Immutable snapshot of the current StaffCore user for authorization checks.

 */

final class StaffUserContext

{

    public function __construct(

        public readonly int $staffUserId,

        public readonly int $platformUserId,

        public readonly string $role,

        public readonly string $status,

        public readonly string $managerScopeMode = ManagerScopeModeRegistry::AUTO,

        private readonly bool $isPlatformAdminFallback = false,

    ) {

    }



    public static function fromStaffUser(StaffUser $user, bool $isPlatformAdminFallback = false): self

    {

        return new self(

            staffUserId: $user->id,

            platformUserId: $user->platformUserId,

            role: $user->role,

            status: $user->status,

            managerScopeMode: $user->managerScopeMode,

            isPlatformAdminFallback: $isPlatformAdminFallback,

        );

    }



    /**

     * Synthetic context when the platform grants temporary StaffCore admin access

     * without a staff_user row (adapter-only; see auth-completion spec).

     */

    public static function platformAdminFallback(int $platformUserId): self

    {

        return new self(

            staffUserId: 0,

            platformUserId: $platformUserId,

            role: RoleRegistry::ROLE_ADMINISTRATOR,

            status: StaffUser::STATUS_ACTIVE,

            isPlatformAdminFallback: true,

        );

    }



    public function isActive(): bool

    {

        return $this->status === StaffUser::STATUS_ACTIVE;

    }



    public function isPlatformAdminFallback(): bool

    {

        return $this->isPlatformAdminFallback;

    }

}


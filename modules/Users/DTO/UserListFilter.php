<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

final class UserListFilter
{
    public const QUERY_ROLE = 'sc_role';

    public const QUERY_STATUS = 'sc_status';

    public const QUERY_SEARCH = 'sc_q';

    public function __construct(
        public readonly ?string $role = null,
        public readonly ?string $status = null,
        public readonly ?string $search = null,
    ) {
    }

    public function hasActiveCriteria(): bool
    {
        return $this->role !== null
            || $this->status !== null
            || $this->search !== null;
    }

    /**
     * @return array<string, scalar>
     */
    public function toQueryArgs(): array
    {
        $args = [];

        if ($this->role !== null) {
            $args[self::QUERY_ROLE] = $this->role;
        }

        if ($this->status !== null) {
            $args[self::QUERY_STATUS] = $this->status;
        }

        if ($this->search !== null) {
            $args[self::QUERY_SEARCH] = $this->search;
        }

        return $args;
    }
}

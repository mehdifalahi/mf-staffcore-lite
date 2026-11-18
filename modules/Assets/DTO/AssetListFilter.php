<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Assets\DTO;

final class AssetListFilter
{
    public const QUERY_CATEGORY = 'sc_category';

    public const QUERY_STATUS = 'sc_status';

    public const QUERY_ASSIGNED_TO = 'sc_employee';

    public const QUERY_SEARCH = 'sc_q';

    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $status = null,
        public readonly ?int $assignedToEmployeeId = null,
        public readonly ?string $search = null,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toQueryArgs(): array
    {
        $args = [];

        if ($this->categoryId !== null) {
            $args[self::QUERY_CATEGORY] = $this->categoryId;
        }

        if ($this->status !== null) {
            $args[self::QUERY_STATUS] = $this->status;
        }

        if ($this->assignedToEmployeeId !== null) {
            $args[self::QUERY_ASSIGNED_TO] = $this->assignedToEmployeeId;
        }

        if ($this->search !== null) {
            $args[self::QUERY_SEARCH] = $this->search;
        }

        return $args;
    }
}

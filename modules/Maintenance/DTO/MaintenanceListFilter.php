<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Maintenance\DTO;

final class MaintenanceListFilter
{
    public const QUERY_ASSET = 'sc_asset';

    public const QUERY_TYPE = 'sc_type';

    public const QUERY_EMPLOYEE = 'sc_employee';

    public const QUERY_DATE_FROM = 'sc_date_from';

    public const QUERY_DATE_TO = 'sc_date_to';

    public function __construct(
        public readonly ?int $assetId = null,
        public readonly ?string $type = null,
        public readonly ?int $performedByEmployeeId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toQueryArgs(): array
    {
        $args = [];

        if ($this->assetId !== null) {
            $args[self::QUERY_ASSET] = $this->assetId;
        }

        if ($this->type !== null) {
            $args[self::QUERY_TYPE] = $this->type;
        }

        if ($this->performedByEmployeeId !== null) {
            $args[self::QUERY_EMPLOYEE] = $this->performedByEmployeeId;
        }

        if ($this->dateFrom !== null) {
            $args[self::QUERY_DATE_FROM] = $this->dateFrom;
        }

        if ($this->dateTo !== null) {
            $args[self::QUERY_DATE_TO] = $this->dateTo;
        }

        return $args;
    }
}

<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Users\DTO;

final class BulkLinkResult
{
    /**
     * @param list<BulkLinkOutcome> $linked
     * @param list<BulkLinkOutcome> $skipped
     * @param list<BulkLinkOutcome> $failed
     */
    public function __construct(
        public readonly array $linked,
        public readonly array $skipped,
        public readonly array $failed,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'linked' => array_map(static fn (BulkLinkOutcome $o): array => $o->toArray(), $this->linked),
            'skipped' => array_map(static fn (BulkLinkOutcome $o): array => $o->toArray(), $this->skipped),
            'failed' => array_map(static fn (BulkLinkOutcome $o): array => $o->toArray(), $this->failed),
            'linked_count' => count($this->linked),
            'skipped_count' => count($this->skipped),
            'failed_count' => count($this->failed),
        ];
    }
}

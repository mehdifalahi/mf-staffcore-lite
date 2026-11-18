<?php

declare(strict_types=1);

namespace MF\StaffCore\Support;

final class BulkDeleteResult
{
    /**
     * @param list<BulkDeleteOutcome> $deleted
     * @param list<BulkDeleteOutcome> $skipped
     * @param list<BulkDeleteOutcome> $failed
     */
    public function __construct(
        public readonly array $deleted,
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
            'deleted' => array_map(static fn (BulkDeleteOutcome $o): array => $o->toArray(), $this->deleted),
            'skipped' => array_map(static fn (BulkDeleteOutcome $o): array => $o->toArray(), $this->skipped),
            'failed' => array_map(static fn (BulkDeleteOutcome $o): array => $o->toArray(), $this->failed),
            'deleted_count' => count($this->deleted),
            'skipped_count' => count($this->skipped),
            'failed_count' => count($this->failed),
        ];
    }
}

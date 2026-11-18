<?php

declare(strict_types=1);

namespace MF\StaffCore\Support;

final class BulkDeleteExecutor
{
    public const MAX_IDS = 100;

    /**
     * @param list<int> $ids
     * @param callable(int): void $deleteOne
     * @param callable(int): (?string) $skipReason Optional; return reason string to skip, null to proceed.
     */
    public static function execute(array $ids, callable $deleteOne, ?callable $skipReason = null): BulkDeleteResult
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            throw new ValidationException(esc_html('Select at least one item.'));
        }

        if (count($ids) > self::MAX_IDS) {
            throw new ValidationException(esc_html('Maximum ' . self::MAX_IDS . ' items per bulk delete request.'));
        }

        /** @var list<BulkDeleteOutcome> $deleted */
        $deleted = [];
        /** @var list<BulkDeleteOutcome> $skipped */
        $skipped = [];
        /** @var list<BulkDeleteOutcome> $failed */
        $failed = [];

        foreach ($ids as $id) {
            if ($skipReason !== null) {
                $reason = $skipReason($id);

                if ($reason !== null && $reason !== '') {
                    $skipped[] = new BulkDeleteOutcome($id, $reason);

                    continue;
                }
            }

            try {
                $deleteOne($id);
                $deleted[] = new BulkDeleteOutcome($id);
            } catch (ValidationException $exception) {
                $failed[] = new BulkDeleteOutcome($id, $exception->getMessage());
            } catch (\RuntimeException $exception) {
                $failed[] = new BulkDeleteOutcome($id, $exception->getMessage());
            }
        }

        return new BulkDeleteResult($deleted, $skipped, $failed);
    }
}

